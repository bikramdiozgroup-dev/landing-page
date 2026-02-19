<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Unsubscribe | Dioz Group</title>
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: Arial, sans-serif;
        min-height: 100vh;
        display: flex;
        justify-content: center;
        align-items: center;
        position: relative;
        overflow: hidden;
    }

    body::before {
        content: '';
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(0, 0, 0, 0.4);
        z-index: 1;
    }

    video {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        object-fit: cover;
        z-index: 0;
    }

    .unsubscribe-container {
        position: relative;
        z-index: 2;
        background-color: rgba(255, 255, 255, 0.15);
        padding: 40px 45px;
        border-radius: 16px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        text-align: center;
        max-width: 420px;
        width: 90%;
        backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.25);
        animation: slideUp 0.6s ease-out;
    }

    @keyframes slideUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .unsubscribe-container img {
        max-width: 140px;
        margin-bottom: 20px;
        opacity: 0.95;
    }

    h2 {
        color: #ffffff;
        margin-bottom: 12px;
        font-size: 24px;
        font-weight: 700;
    }

    p {
        color: #f0f0f0;
        font-size: 15px;
        margin-bottom: 20px;
        line-height: 1.5;
    }

    input[type="email"] {
        padding: 12px 15px;
        width: 100%;
        margin-bottom: 15px;
        border: 1px solid rgba(255, 255, 255, 0.3);
        border-radius: 8px;
        font-size: 14px;
        background-color: rgba(255, 255, 255, 0.15);
        color: #ffffff;
        transition: all 0.3s;
    }

    input[type="email"]::placeholder {
        color: rgba(255, 255, 255, 0.7);
    }

    input[type="email"]:focus {
        outline: none;
        border-color: #ff6600;
        box-shadow: 0 0 0 3px rgba(255, 102, 0, 0.2);
        background-color: rgba(255, 255, 255, 0.25);
    }

    button {
        padding: 12px 30px;
        background-color: #ff6600;
        color: #ffffff;
        border: none;
        border-radius: 8px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(255, 102, 0, 0.3);
        width: 100%;
    }

    button:hover {
        background-color: #e65c00;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(255, 102, 0, 0.4);
    }

    button:active {
        transform: translateY(0);
    }

    button:disabled {
        opacity: 0.7;
        cursor: not-allowed;
    }

    .error-message {
        background-color: rgba(255, 100, 100, 0.2);
        border: 1px solid rgba(255, 100, 100, 0.5);
        color: #ffcccc;
        padding: 10px 12px;
        border-radius: 6px;
        margin-bottom: 15px;
        display: none;
        font-size: 13px;
    }

    .error-message.show {
        display: block;
    }

    .loading {
        display: none;
        color: #f0f0f0;
        font-size: 13px;
    }

    .loading.show {
        display: block;
    }
</style>
</head>
<body>

<!-- Video Background -->
<video autoplay muted loop playsinline>
    <source src="https://dioz.com/wp-content/uploads/2024/06/Banner-Video-Final.mp4" type="video/mp4">
    Your browser does not support the video tag.
</video>

<div class="unsubscribe-container">
    <img src="https://dioz.com/wp-content/uploads/2024/07/logo.svg" alt="Dioz Logo">
    <h2>Unsubscribe from Our Emails</h2>
    <p>Enter your email below to unsubscribe from our mailing list:</p>
    
    <div class="error-message" id="errorMessage"></div>
    <div class="loading" id="loadingMessage">Validating email...</div>
    
    <form id="unsubscribeForm" action="unsubscribe-handler.php" method="POST">
        <input 
            type="email" 
            id="emailInput"
            name="email" 
            placeholder="Your email address" 
            required
        >
        <br>
        <button type="submit" id="submitBtn">Unsubscribe</button>
    </form>
</div>

<script>
document.getElementById('unsubscribeForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const email = document.getElementById('emailInput').value.trim();
    const errorEl = document.getElementById('errorMessage');
    const loadingEl = document.getElementById('loadingMessage');
    const submitBtn = document.getElementById('submitBtn');
    
    // Reset messages
    errorEl.classList.remove('show');
    errorEl.textContent = '';
    loadingEl.classList.add('show');
    submitBtn.disabled = true;
    
    try {
        const response = await fetch('unsubscribe-handler.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'email=' + encodeURIComponent(email)
        });
        
        if (response.redirected) {
            // Successful redirect
            window.location.href = response.url;
            return;
        }
        
        const data = await response.json();
        
        if (!data.success) {
            errorEl.textContent = '❌ ' + (data.message || 'Validation failed. Please check your email.');
            errorEl.classList.add('show');
            loadingEl.classList.remove('show');
            submitBtn.disabled = false;
        } else {
            // Success - let the form redirect
            window.location.href = '/unsubscribe-success.html';
        }
    } catch (err) {
        console.error('Error:', err);
        errorEl.textContent = '❌ Network error. Please try again.';
        errorEl.classList.add('show');
        loadingEl.classList.remove('show');
        submitBtn.disabled = false;
    }
});
</script>

</body>
</html>
