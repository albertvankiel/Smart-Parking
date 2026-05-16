const express = require('express');
const http = require('http');
const { Server } = require('socket.io');

const app = express();
const server = http.createServer(app);

// Allow CORS
const io = new Server(server, {
	cors: {
		origin: "*",
		methods: ["GET", "POST", "PUT"]
	}
});

// Middleware to parse JSON from the API
app.use(express.json());

// Websocket connection from frontend
io.on('connection', (socket) => {
	console.log('Client connected:', socket.id);

	socket.on('disconnect', () => {
		console.log('Client disconnected:', socket.id);
	});
})

// REST API requests from the API
app.post('/broadcast/booking', (req, res) => {
	const { reservation } = req.body;

	if (!reservation) {
		return res.status(400).json({ error: "Missing reservation data" });
	}

	console.log('Broadcasting new booking:', reservation);

	// Broadcast event to all websocket clients (active users)
	io.emit('spot_booked', reservation);
	res.status(200).json({success: true});
});

const PORT = 3000;
server.listen(PORT, () => {
	console.log(`Websocket server running on port ${PORT}`);
});
