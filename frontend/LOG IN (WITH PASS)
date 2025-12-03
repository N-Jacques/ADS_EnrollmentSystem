<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Student Enrollment</title>

    <style>
        html, body {
            margin: 0;
            padding: 0;
        }

        body {
            font-family: Arial, sans-serif;
            background-image: url('https://plm.edu.ph/FooterImage.jpg');
            background-repeat: no-repeat;
            background-attachment: fixed;
            background-size: cover;
            background-position: center;
        }

        .header {
            max-width: 100%;
            max-height: 60px;
            padding: 15px 25px;
            background-color: white;
            display: flex;
            flex-direction: row;
            gap: 10px;
            box-shadow: 0px 1px 10px 1px#52525286;
            align-items: center;
        }

        .header p {
            font-family: 'Times New Roman', Times, serif; 
            color: #b18819; 
            font-size: 25px; 
            font-weight: bold; 
            vertical-align: middle; 
        }

        .header img {
            width: 60px;
            height: 60px;
        }

        .tabs-btn { 
            display: flex;
            font-size: 22px;
            position: relative;
            display: inline-flex;
            text-align: center;
            background-color: transparent;
            color: 080808;
            padding: 10px 20px;
            border-width: 0px;
            cursor: pointer;
            position: absolute;
            right: 3.5%;
        }

        .tabs-btn::after {
        content: '';
        position: absolute;
        align-self: center;
        left: 0;
        right: 0;
        bottom: -5px; 
        width: 0;
        height: 2px;
        background-color: #b18819; 
        text-decoration-thickness: 3px;
        transition: width 0.4s ease-in-out; 
        }

        .tabs-btn:hover::after {
        width: 100%; 
        }

        .login-card {
            background-color: white;
            width: 400px;        
            margin: 0;
            position: absolute;
            top: 55%;
            left: 50%;
            transform: translate(-50%, -50%);   
            padding: 30px 35px;       
            border-radius: 30px;
            text-align: center;
            align-items: center;
            box-shadow: 0px 1px 10px 1px #52525286;        
        }

        .login-card h1 {
            color: #080808;
            margin: 0 0 25px 0;
            font-size: 30px;
            font-weight: bold;
        }

        label {
            font-size: 20px;
            font-weight: 500;
            display: block;           
            text-align: left;         
            margin-left: 5px;
        }

        .input-field {
            width: 90%;               
            padding: 15px;
            margin: 10px auto 25px auto; 
            border-radius: 12px;
            background-color: #e7e6e6;
            border: 1px solid #ccc;
            font-size: 18px;
            display: block;
        }

        .login-btn {
            width: 120px;
            padding: 10px;
            font-size: 18px;
            font-weight: 600;
            color: white;
            background-color: #b18819;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            margin-top: 5px;
        }

        .login-btn:hover {
            background-color: #8a6912;
        }

        .tabs-btn:hover {
            color: #b18819;
        }
    </style>
</head>

<body>

    <div class = "header">
        <img src = "https://plm.edu.ph/assets/plm-logo.DLcRDINN.png">

        <p>PAMANTASAN NG LUNGSOD NG MAYNILA
        </p>    
        
        <button class="tabs-btn" onclick="openPlmWeb()">About</button>
            
    </div>

    <div class="login-card">
        <h1>Log in</h1>

        <label>Student no.</label>
        <input type="text" placeholder ="ex. 2023*****" class="input-field">

        <label>Password</label>
        <input type="password"  class="input-field">
        
        <button class="login-btn" onclick="openProfile()">Log in</button>
    </div>

    <script>
        function openPlmWeb() { location.href = "https://plm.edu.ph/studentinfosys"; }
        function openProfile() { window.location.href = "ads_studentprofile.html"; }
    </script>

</body>
</html>
