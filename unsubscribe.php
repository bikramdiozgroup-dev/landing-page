<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Unsubscribe | Dioz Group</title>
<style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f9f9f9;
        display: flex;
        justify-content: center;
        align-items: center;
        height: 100vh;
        margin: 0;
    }
    .unsubscribe-container {
        background-color: #ffffff;
        padding: 30px 40px;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        text-align: center;
        max-width: 400px;
        width: 90%;
    }
    .unsubscribe-container img {
        max-width: 150px;
        margin-bottom: 20px;
    }
    h2 {
        color: #333333;
        margin-bottom: 15px;
    }
    p {
        color: #555555;
        font-size: 14px;
        margin-bottom: 20px;
    }
    input[type="email"] {
        padding: 10px;
        width: 80%;
        margin-bottom: 15px;
        border: 1px solid #ccc;
        border-radius: 6px;
        font-size: 14px;
    }
    button {
        padding: 10px 25px;
        background-color: #ff6600;
        color: #ffffff;
        border: none;
        border-radius: 6px;
        font-size: 14px;
        cursor: pointer;
    }
    button:hover {
        background-color: #e65c00;
    }
</style>
</head>
<body>
<div class="unsubscribe-container">
    <!-- Replace the src below with your Dioz logo URL -->
    <img src="https://dioz.com/wp-content/uploads/2024/07/logo.svg" alt="Dioz Logo">
    <h2>Unsubscribe from Our Emails</h2>
    <p>Enter your email below to unsubscribe:</p>
    <form action="unsubscribe-handler.php" method="POST">
        <input type="email" name="email" placeholder="Your email" required>
        <br>
        <button type="submit">Unsubscribe</button>
    </form>
</div>
</body>
</html>
