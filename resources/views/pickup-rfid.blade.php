<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RFID Pickup Verification</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            padding: 40px;
            max-width: 600px;
            width: 100%;
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
            font-size: 28px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }

        input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.1);
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        button {
            flex: 1;
            padding: 12px;
            font-size: 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-primary {
            background-color: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background-color: #0056b3;
        }

        .btn-primary:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        .btn-secondary {
            background-color: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background-color: #545b62;
        }

        .btn-secondary:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        .status-message {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            display: none;
        }

        .status-message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            display: block;
        }

        .status-message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            display: block;
        }

        .status-message.info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
            display: block;
        }

        .log-area {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin-top: 20px;
            max-height: 200px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            color: #333;
        }

        .log-entry {
            padding: 4px 0;
            border-bottom: 1px solid #eee;
        }

        .log-entry:last-child {
            border-bottom: none;
        }

        .unsupported-message {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
            padding: 15px;
            border-radius: 4px;
            display: none;
            margin-bottom: 20px;
        }

        .scanner-status {
            font-size: 14px;
            color: #666;
            margin-top: 10px;
        }

        .scanner-status.connected {
            color: #28a745;
        }

        .scanner-status.disconnected {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>RFID Pickup Verification</h1>

        <div class="unsupported-message" id="unsupportedMessage">
            ⚠️ Use Chrome or Edge on localhost for USB scanner support.
        </div>

        <div class="form-group">
            <label for="orderId">Order ID</label>
            <input type="text" id="orderId" placeholder="Enter Order ID (e.g., OR1)">
        </div>

        <div class="button-group">
            <button class="btn-primary" id="connectBtn" onclick="connectScanner()">
                Connect Scanner
            </button>
            <button class="btn-secondary" id="disconnectBtn" onclick="disconnectScanner()" disabled>
                Disconnect Scanner
            </button>
        </div>

        <div class="scanner-status disconnected" id="scannerStatus">
            Scanner: Disconnected
        </div>

        <div class="status-message" id="statusMessage"></div>

        <div class="button-group">
            <button class="btn-secondary" id="manualBtn" onclick="markPickedUpManually()">
                Mark Picked Up Manually
            </button>
        </div>

        <div class="log-area" id="logArea"></div>
    </div>

    <script>
        let port = null;
        let reader = null;
        let isReadingPort = false;
        let scanAllowed = false;
        let scannerReady = false;
        let scanCooldown = false;

        // Dynamically determine API endpoint based on environment
        const PICKUP_CONFIRM_URL = (() => {
            const path = window.location.pathname;
            
            if (path.startsWith('/GG-TP/')) {
                return '/GG-TP/api/pickup-rfid/confirm';
            }
            
            return '/api/pickup-rfid/confirm';
        })();

        // Check if Web Serial API is supported
        if (!navigator.serial) {
            document.getElementById('unsupportedMessage').style.display = 'block';
            document.getElementById('connectBtn').disabled = true;
        }

        function log(message) {
            const logArea = document.getElementById('logArea');
            const entry = document.createElement('div');
            entry.className = 'log-entry';
            entry.textContent = `[${new Date().toLocaleTimeString()}] ${message}`;
            logArea.appendChild(entry);
            logArea.scrollTop = logArea.scrollHeight;
        }

        function showStatus(message, type) {
            const statusMessage = document.getElementById('statusMessage');
            statusMessage.textContent = message;
            statusMessage.className = `status-message ${type}`;
        }

        async function connectScanner() {
            try {
                port = await navigator.serial.requestPort();
                await port.open({ baudRate: 115200 });
                
                scanAllowed = false;
                scannerReady = false;
                scanCooldown = false;
                
                document.getElementById('connectBtn').disabled = true;
                document.getElementById('disconnectBtn').disabled = false;
                document.getElementById('scannerStatus').textContent = 'Scanner: Connected';
                document.getElementById('scannerStatus').classList.remove('disconnected');
                document.getElementById('scannerStatus').classList.add('connected');
                
                log('Scanner connected. Waiting for NodeMCU to restart...');
                showStatus('Scanner connected. Waiting for NodeMCU to restart...', 'info');
                
                // Allow scanning after 6 seconds if RFID_READY not received
                setTimeout(() => {
                    scanAllowed = true;
                    if (!scannerReady) {
                        log('Scan allowed (timeout - RFID_READY not received in 6s)');
                    }
                }, 6000);

                // Show fallback message after 8 seconds if RFID_READY not received
                setTimeout(() => {
                    if (!scannerReady) {
                        showStatus('Scanner connected. Try scanning now. If it does not scan, press reset once.', 'info');
                    }
                }, 8000);
                
                readSerialPort();
            } catch (error) {
                if (error.name === 'NotFoundError') {
                    log('No port selected');
                } else if (error.name === 'InvalidStateError') {
                    log('Port is already open');
                } else {
                    log(`Connection error: ${error.message}`);
                    showStatus(`Connection error: ${error.message}`, 'error');
                }
            }
        }

        async function readSerialPort() {
            if (!port || !port.readable) return;
            
            isReadingPort = true;
            reader = port.readable.getReader();

            try {
                let buffer = '';

                while (isReadingPort) {
                    const { value, done } = await reader.read();

                    if (done) {
                        break;
                    }

                    const text = new TextDecoder().decode(value);
                    buffer += text;

                    const lines = buffer.split('\n');
                    buffer = lines.pop();

                    for (const line of lines) {
                        const trimmedLine = line.trim();
                        
                        if (trimmedLine) {
                            log(`Raw: ${trimmedLine}`);
                        }

                        if (trimmedLine.includes('RFID_READY')) {
                            scannerReady = true;
                            scanAllowed = true;
                            log('RFID_READY received from device');
                            showStatus('Scanner ready. Please scan RFID card.', 'info');
                        }

                        if (trimmedLine.includes('RFID_SCANNED')) {
                            log('RFID_SCANNED received');
                            
                            if (!scanAllowed) {
                                log('Scan ignored because scanner is still warming up.');
                                continue;
                            }

                            if (scanCooldown) {
                                log('Duplicate scan ignored during cooldown.');
                                continue;
                            }

                            scanCooldown = true;
                            setTimeout(() => {
                                scanCooldown = false;
                            }, 2000);

                            log('RFID card detected!');
                            await handleRfidScanned();
                        }
                    }
                }
            } catch (error) {
                if (error.name !== 'AbortError') {
                    log(`Serial read error: ${error.message}`);
                    showStatus(`Serial read error: ${error.message}`, 'error');
                }
            } finally {
                reader.releaseLock();
            }
        }

        async function handleRfidScanned() {
            const orderId = document.getElementById('orderId').value.trim();

            if (!orderId) {
                showStatus('Please enter an Order ID before scanning.', 'error');
                log('RFID scan ignored: No Order ID provided');
                return;
            }

            try {
                log('Sending pickup request to: ' + PICKUP_CONFIRM_URL);
                const response = await fetch(PICKUP_CONFIRM_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ order_id: orderId }),
                });

                const text = await response.text();
                let data;

                try {
                    data = JSON.parse(text);
                } catch (parseError) {
                    const preview = text.length > 300 ? text.substring(0, 300) + '...' : text;
                    console.error('Non-JSON response:', text);
                    throw new Error('Server returned HTML instead of JSON. Check api.php route and bootstrap/app.php api route loading.');
                }

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Pickup failed.');
                }

                showStatus(`✓ ${data.message}`, 'success');
                log(`Success: ${data.message}`);
                document.getElementById('orderId').value = '';
            } catch (error) {
                showStatus(`Network error: ${error.message}`, 'error');
                log(`Network error: ${error.message}`);
            }
        }

        async function disconnectScanner() {
            isReadingPort = false;

            if (reader) {
                try {
                    await reader.cancel();
                } catch (error) {
                    log(`Cancel error: ${error.message}`);
                }
            }

            if (port) {
                try {
                    await port.close();
                } catch (error) {
                    log(`Close error: ${error.message}`);
                }
                port = null;
            }

            scanAllowed = false;
            scannerReady = false;
            scanCooldown = false;

            document.getElementById('connectBtn').disabled = false;
            document.getElementById('disconnectBtn').disabled = true;
            document.getElementById('scannerStatus').textContent = 'Scanner: Disconnected';
            document.getElementById('scannerStatus').classList.remove('connected');
            document.getElementById('scannerStatus').classList.add('disconnected');

            log('Scanner disconnected');
            showStatus('Scanner disconnected.', 'info');
        }

        async function markPickedUpManually() {
            const orderId = document.getElementById('orderId').value.trim();

            if (!orderId) {
                showStatus('Please enter an Order ID.', 'error');
                return;
            }

            try {
                log('Sending pickup request to: ' + PICKUP_CONFIRM_URL);
                const response = await fetch(PICKUP_CONFIRM_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ order_id: orderId }),
                });

                const text = await response.text();
                let data;

                try {
                    data = JSON.parse(text);
                } catch (parseError) {
                    const preview = text.length > 300 ? text.substring(0, 300) + '...' : text;
                    console.error('Non-JSON response:', text);
                    throw new Error('Server returned HTML instead of JSON. Check api.php route and bootstrap/app.php api route loading.');
                }

                if (!response.ok || !data.success) {
                    throw new Error(data.message || 'Pickup failed.');
                }

                showStatus(`✓ ${data.message}`, 'success');
                log(`Manual confirmation: ${data.message}`);
                document.getElementById('orderId').value = '';
            } catch (error) {
                showStatus(`Network error: ${error.message}`, 'error');
                log(`Network error: ${error.message}`);
            }
        }

        // Clean up on page unload
        window.addEventListener('beforeunload', async () => {
            await disconnectScanner();
        });
    </script>

</body>
</html>
