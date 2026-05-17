<template>
	<div class="slots-grid">
		<div v-if="isLoading" class="placeholder-msg">
			<div class="loader"></div>
			<p>Loading parking spots...</p>
		</div>

		<div v-else-if="spots.length === 0" class="placeholder-msg" style="color:red">
			<p>No parking spots available.</p>
		</div>

		<template v-else>
			<div v-for="spot in spots" :key="spot.id" class="slot-card">
				<div class="slot-header">
					<h3>Spot {{ spot.spotNumber }}</h3>
					<span :class="['badge', spot.spotType === 'handicapped' ? 'badge-blue': 'badge-gray']">
						{{ spot.spotType }}
					</span>
				</div>
				<div class="slot-details">
					<p>Floor {{ spot.floorNumber }}</p>
				</div>

				<div class="time-slots">
					<button
						v-for="timeSlot in timeSlots"
						:key="timeSlot.id"
						:class="['time-slot-btn', isBooked(spot.id, timeSlot) ? 'booked' : 'available']"
						:disabled="isBookedByOther(spot.id, timeSlot) || isBooking"
						@click="handleSlotClick(spot.id, timeSlot)"
					>
						<span class="status-dot"></span>
						{{ isBookedByMe(spot.id, timeSlot) ? 'Release' : timeSlot.label }}
					</button>
				</div>
			</div>
		</template>
	</div>
</template>

<script setup>
import {ref, onMounted, onUnmounted} from 'vue';
import AuthService from '../services/AuthService';
import { io } from 'socket.io-client';

const spots = ref([]);
const reservations = ref([]);
const isLoading = ref(true);
const isBooking = ref(false);
const error = ref(null);
const selectedDate = ref(document.getElementById('date-select')?.value || '');

const wsUrl = import.meta.env.VITE_WEBSOCKET_URL || 'http://localhost:3000';
let socket = null;

const timeSlots = [
	{id: 1, label: '08:00 - 12:00', startTime: '08:00:00', endTime: '12:00:00'},
	{id: 2, label: '12:00 - 16:00', startTime: '12:00:00', endTime: '16:00:00'},
	{id: 3, label: '16:00 - 20:00', startTime: '16:00:00', endTime: '20:00:00'}
];

const fetchSpots = async() => {
	isLoading.value = true;
	error.value = null;

	try {
		const token = AuthService.getToken();
		const response = await fetch('/api/spots', {
			headers: {
				'Authorization': `Bearer ${token}`
			}
		});

		const data = await response.json();

		if (response.ok) {
			spots.value = data.data;
		} else {
			error.value = data.error || 'Failed to load spots;'
		}

	} catch(err) {
		error.value = 'Network error';
	} finally {
		isLoading.value = false;
	}
};

const getReservation = (spotId, timeSlot) => {
	const startDateTime = `${selectedDate.value} ${timeSlot.startTime}`;
	return reservations.value.find(res => 
		res.parking_spot_id === spotId &&
		res.start_time === startDateTime &&
		res.status === 'booked'
	);
};

const isBookedByMe = (spotId, timeSlot) => {
	const reservation = getReservation(spotId, timeSlot);
	if (!reservation) {
		return false;
	}
	const token = AuthService.getToken();
	if (!token) {
		return false;
	}
	try {
		const payload = JSON.parse(atob(token.split('.')[1]));
		return reservation.user_id === payload.sub;
	} catch (e) {
		return false;
	}
};

const isBookedByOther = (spotId, timeSlot) => {
	return isBooked(spotId, timeSlot) && !isBookedByMe(spotId, timeSlot);
};

const releaseSpot = async (reservationId) => {
	isBooking.value = true;
	try {
		const token = AuthService.getToken();
		const response = await fetch(`/api/reservations/${reservationId}/complete`, {
			method: 'PUT',
			headers: {
				'Authorization': `Bearer ${token}`
			}
		});
		const data = await response.json();
		if (response.ok) {
			// Remove the reservation from the local array so the UI updates instantly
			reservations.value = reservations.value.filter(res => res.id !== reservationId);
			alert("Spot released successfully!");
		} else {
			alert(`Could not release spot: ${data.message || data.error}`);
		}
	} catch(err) {
		alert("Network error occurred");
	} finally {
		isBooking.value = false;
	}
};

const handleSlotClick = (spotId, timeSlot) => {
	if (isBookedByMe(spotId, timeSlot)) {
		const reservation = getReservation(spotId, timeSlot);
		if (reservation) {
			releaseSpot(reservation.id);
		}
	} else {
		bookSpot(spotId, timeSlot);
	}
};

const isBooked = (spotId, timeSlot) => {
	const startDateTime = `${selectedDate.value} ${timeSlot.startTime}`;

	return reservations.value.some(res => 
		res.parking_spot_id === spotId &&
		res.start_time === startDateTime && 
		res.status === 'booked'
	);
};

const bookSpot = async (spotId, timeSlot) => {
	if (!selectedDate.value) {
		alert("Please pick a date first");
		return;
	}

	isBooking.value = true;
	const startDateTime = `${selectedDate.value} ${timeSlot.startTime}`;
	const endDateTime = `${selectedDate.value} ${timeSlot.endTime}`;

	try {
		const token = AuthService.getToken();
		const response = await fetch('/api/reservations', {
			method: 'POST',
			headers: {
				'Content-Type': 'application/json',
				'Authorization': `Bearer ${token}`
			},
			body: JSON.stringify({
				spot_id: spotId,
				start_time: startDateTime,
				end_time: endDateTime
			})
		});

		const data = await response.json();

		if (response.ok) {
			alert("Spot booked successfully!");
		} else {
			// Catch 409 from pessimistic lock
			alert(`Booking failed: ${data.message || data.error}`);
		}

	} catch(err) {
		alert("Network error occurred");
	} finally {
		isBooking.value = false;
	}
}

const handleDateChange = (event) => {
	selectedDate.value = event.detail;
	fetchReservations();
}

const fetchReservations = async() => {
	if (!selectedDate.value) {
		return;
	}

	try {
		const token = AuthService.getToken();
		const response = await fetch(`/api/reservations?date=${selectedDate.value}`, {
			method: 'GET',
			headers: {
				'Authorization': `Bearer ${token}`
			}
		});
		const data = await response.json();

		if (response.ok) {
			reservations.value = data.data;
		} else {
			console.error("Failed to load reservations:", data.error);
		}
	} catch (err) {
		console.error("Network error fetching reservations");
	}
}

onMounted(() => {
	fetchSpots();
	fetchReservations();
	window.addEventListener('parking-date-change', handleDateChange);

	socket = io(wsUrl);

	socket.on('spot_booked', (newReservation) => {
		console.log('Receieved real time booking:', newReservation);

		if (newReservation.start_time.startsWith(selectedDate.value)) {
			reservations.value.push(newReservation);
		}
	});

	socket.on('spot_released', (data) => {
		console.log('Received real time release for reservation:', data.reservation_id);
		
		reservations.value = reservations.value.filter(res => res.id !== data.reservation_id);
	});
});

onUnmounted(() => {
	window.removeEventListener('parking-date-change', handleDateChange);
	if (socket) {
		socket.disconnect();
	}
});

</script>

<style scoped>
.time-slots {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-top: auto;
}
.time-slot-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    width: 100%;
    padding: 0.5rem 1rem;
    border-radius: 6px;
    border: 1px solid #e5e7eb;
    background: white;
    font-size: 0.85rem;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    height: auto;
    color: #374151;
    box-shadow: none;
}
.time-slot-btn:hover:not(:disabled) {
    background: #f9fafb;
    border-color: #d1d5db;
    transform: none;
    box-shadow: none;
}
.status-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
}
.time-slot-btn.available .status-dot {
    background-color: #10b981; /* Green */
}
.time-slot-btn.booked {
    background-color: #f3f4f6;
    color: #9ca3af;
    opacity: 0.8;
}

.time-slot-btn.booked:disabled {
    cursor: not-allowed;
}

.time-slot-btn.booked:not(:disabled) {
    background-color: #fee2e2;
    color: #b91c1c;
    border-color: #fca5a5;
    cursor: pointer;
    opacity: 1;
}
.time-slot-btn.booked:not(:disabled):hover {
    background-color: #fecaca;
}
.time-slot-btn.booked .status-dot {
    background-color: #ef4444;
}
.time-slot-btn.booked .status-dot {
    background-color: #ef4444;
}
</style>