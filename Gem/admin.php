<?php
require_once 'config.php';
session_start();

if (isset($_POST['login'])) {
    if (password_verify($_POST['password'], $admin_password_hash)) {
        $_SESSION['admin_logged_in'] = true;
    } else {
        $login_error = 'رمز عبور اشتباه است!';
    }
}

if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// صفحه لاگین
if (!isset($_SESSION['admin_logged_in'])) {
    ?>
    <!DOCTYPE html>
    <html lang="fa" dir="rtl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>ورود مدیریت</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet">
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Vazirmatn', sans-serif; }
            
            body {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                height: 100vh;
                display: flex;
                justify-content: center;
                align-items: center;
                overflow: hidden;
            }
            
            .login-card {
                background: rgba(255, 255, 255, 0.95);
                border-radius: 24px;
                padding: 40px 35px;
                width: 380px;
                box-shadow: 0 20px 60px rgba(0,0,0,0.2);
                text-align: center;
            }
            
            .login-icon {
                width: 80px;
                height: 80px;
                background: linear-gradient(135deg, #667eea, #764ba2);
                border-radius: 20px;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 20px;
                box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
                transform: rotate(45deg);
            }
            
            .login-icon i {
                font-size: 32px;
                color: #fff;
                transform: rotate(-45deg);
            }
            
            h2 {
                color: #1a202c;
                font-size: 22px;
                margin-bottom: 25px;
                font-weight: 900;
            }
            
            .input-group {
                position: relative;
                margin-bottom: 20px;
            }
            
            .input-group i {
                position: absolute;
                right: 14px;
                top: 50%;
                transform: translateY(-50%);
                color: #94a3b8;
                font-size: 16px;
            }
            
            input {
                width: 100%;
                padding: 14px 42px 14px 14px;
                background: #f8fafc;
                border: 2px solid #e2e8f0;
                border-radius: 12px;
                font-size: 14px;
                font-family: 'Vazirmatn', sans-serif;
                outline: none;
                transition: all 0.3s;
            }
            
            input:focus {
                border-color: #667eea;
                background: #fff;
                box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            }
            
            button {
                width: 100%;
                padding: 14px;
                background: linear-gradient(135deg, #667eea, #764ba2);
                color: #fff;
                border: none;
                border-radius: 12px;
                font-size: 15px;
                font-weight: 700;
                cursor: pointer;
                font-family: 'Vazirmatn', sans-serif;
                transition: all 0.3s;
                box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
            }
            
            button:hover {
                transform: translateY(-2px);
                box-shadow: 0 12px 35px rgba(102, 126, 234, 0.4);
            }
            
            .error-msg {
                background: #fef2f2;
                border: 1px solid #fecaca;
                color: #991b1b;
                padding: 10px;
                border-radius: 10px;
                margin-bottom: 15px;
                font-size: 13px;
                display: flex;
                align-items: center;
                gap: 8px;
                justify-content: center;
            }
        </style>
    </head>
    <body>
        <div class="login-card">
            <div class="login-icon">
                <i class="fas fa-shield-halved"></i>
            </div>
            <h2>پنل مدیریت</h2>
            
            <?php if (isset($login_error)): ?>
                <div class="error-msg">
                    <i class="fas fa-circle-exclamation"></i>
                    <?php echo $login_error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="input-group">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="password" placeholder="رمز عبور" required>
                </div>
                <button type="submit" name="login">
                    <i class="fas fa-arrow-right-to-bracket"></i>
                    ورود به پنل
                </button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// عملیات
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $status_map = ['complete' => 'completed', 'reject' => 'rejected', 'pending' => 'pending'];
    if (isset($status_map[$_GET['action']])) {
        $pdo->prepare("UPDATE requests SET status = ? WHERE id = ?")->execute([$status_map[$_GET['action']], $id]);
    }
    header('Location: admin.php?filter=' . ($_GET['filter'] ?? 'all') . '&page=' . ($_GET['page'] ?? 1));
    exit;
}

// آمار
$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM requests")->fetchColumn(),
    'pending' => $pdo->query("SELECT COUNT(*) FROM requests WHERE status='pending'")->fetchColumn(),
    'completed' => $pdo->query("SELECT COUNT(*) FROM requests WHERE status='completed'")->fetchColumn(),
    'rejected' => $pdo->query("SELECT COUNT(*) FROM requests WHERE status='rejected'")->fetchColumn(),
];

// صفحه‌بندی
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 8;
$offset = ($page - 1) * $per_page;

$filter = $_GET['filter'] ?? 'all';
$where = '';
if ($filter === 'pending') $where = "WHERE status='pending'";
elseif ($filter === 'completed') $where = "WHERE status='completed'";
elseif ($filter === 'rejected') $where = "WHERE status='rejected'";

$total = $pdo->query("SELECT COUNT(*) FROM requests $where")->fetchColumn();
$total_pages = ceil($total / $per_page);

$stmt = $pdo->prepare("SELECT * FROM requests $where ORDER BY request_date DESC LIMIT $offset, $per_page");
$stmt->execute();
$requests = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>پنل مدیریت</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/gh/rastikerdar/vazirmatn@v33.003/Vazirmatn-font-face.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Vazirmatn', sans-serif; }
        
        body {
            background: #f1f5f9;
            height: 100vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }
        
        /* هدر */
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 0 25px;
            height: 60px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 15px rgba(102, 126, 234, 0.2);
            flex-shrink: 0;
            z-index: 100;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .logo-icon {
            width: 36px;
            height: 36px;
            background: rgba(255,255,255,0.2);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 16px;
        }
        
        .header h1 {
            color: #fff;
            font-size: 15px;
            font-weight: 800;
        }
        
        .header-right {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .btn-logout {
            padding: 7px 16px;
            background: rgba(255,255,255,0.2);
            color: #fff;
            text-decoration: none;
            border-radius: 8px;
            font-size: 11px;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 5px;
            border: 1px solid rgba(255,255,255,0.2);
        }
        
        .btn-logout:hover {
            background: rgba(255,255,255,0.3);
        }
        
        /* محتوای اصلی */
        .main-content {
            flex: 1;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            padding: 15px 20px;
            gap: 12px;
        }
        
        /* کارت‌های آمار */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            flex-shrink: 0;
        }
        
        .stat-card {
            background: #fff;
            border-radius: 14px;
            padding: 14px 16px;
            box-shadow: 0 1px 5px rgba(0,0,0,0.03);
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid #e2e8f0;
        }
        
        .stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }
        
        .stat-icon.total { background: #eff6ff; color: #667eea; }
        .stat-icon.pending { background: #fffbeb; color: #f59e0b; }
        .stat-icon.completed { background: #ecfdf5; color: #10b981; }
        .stat-icon.rejected { background: #fef2f2; color: #ef4444; }
        
        .stat-info .number {
            font-size: 22px;
            font-weight: 900;
            color: #1e293b;
            line-height: 1.2;
        }
        
        .stat-info .label {
            font-size: 10px;
            color: #94a3b8;
            font-weight: 500;
        }
        
        /* فیلترها */
        .filters-row {
            display: flex;
            gap: 8px;
            flex-shrink: 0;
            flex-wrap: wrap;
        }
        
        .filter-btn {
            padding: 7px 16px;
            background: #fff;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            color: #64748b;
            text-decoration: none;
            font-size: 11px;
            font-weight: 600;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .filter-btn.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #fff;
            border-color: transparent;
        }
        
        .filter-btn:hover:not(.active) {
            border-color: #667eea;
            color: #667eea;
        }
        
        .filter-count {
            background: rgba(255,255,255,0.25);
            padding: 1px 7px;
            border-radius: 15px;
            font-size: 10px;
            font-weight: 700;
        }
        
        /* جدول */
        .table-wrapper {
            flex: 1;
            overflow: auto;
            border-radius: 14px;
            background: #fff;
            border: 1px solid #e2e8f0;
        }
        
        .table-wrapper::-webkit-scrollbar {
            width: 4px;
            height: 4px;
        }
        
        .table-wrapper::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 1300px;
        }
        
        thead th {
            background: #f8fafc;
            padding: 10px 8px;
            text-align: right;
            font-size: 10px;
            color: #64748b;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            position: sticky;
            top: 0;
            z-index: 10;
            border-bottom: 2px solid #e2e8f0;
            white-space: nowrap;
        }
        
        tbody td {
            padding: 10px 8px;
            color: #334155;
            font-size: 11px;
            border-bottom: 1px solid #f1f5f9;
            white-space: nowrap;
        }
        
        tbody tr:hover td {
            background: #f8faff;
        }
        
        .player-id {
            font-weight: 700;
            color: #667eea;
        }
        
        .email-cell {
            direction: ltr;
            text-align: left;
            font-size: 10px;
            color: #64748b;
        }
        
        .code-cell {
            font-family: monospace;
            background: #f1f5f9;
            padding: 2px 6px;
            border-radius: 5px;
            font-size: 10px;
        }
        
        .badge {
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 10px;
            font-weight: 700;
            display: inline-block;
        }
        
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-completed { background: #d1fae5; color: #065f46; }
        .badge-rejected { background: #fee2e2; color: #991b1b; }
        
        .actions {
            display: flex;
            gap: 3px;
        }
        
        .btn-action {
            width: 28px;
            height: 28px;
            border-radius: 7px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 11px;
            transition: all 0.2s;
        }
        
        .btn-complete { background: #d1fae5; color: #065f46; }
        .btn-complete:hover { background: #065f46; color: #fff; }
        
        .btn-reject { background: #fee2e2; color: #991b1b; }
        .btn-reject:hover { background: #991b1b; color: #fff; }
        
        .btn-undo { background: #fef3c7; color: #92400e; }
        .btn-undo:hover { background: #92400e; color: #fff; }
        
        /* صفحه‌بندی */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 5px;
            flex-shrink: 0;
        }
        
        .page-btn {
            width: 32px;
            height: 32px;
            background: #fff;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            color: #64748b;
            font-weight: 600;
            font-size: 11px;
            transition: all 0.2s;
        }
        
        .page-btn.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: #fff;
            border-color: transparent;
        }
        
        .page-btn:hover:not(.active) {
            border-color: #667eea;
            color: #667eea;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px;
            color: #94a3b8;
        }
        
        .empty-state i {
            font-size: 40px;
            margin-bottom: 10px;
            color: #cbd5e1;
        }
    </style>
</head>
<body>
    <!-- هدر -->
    <div class="header">
        <div class="header-left">
            <div class="logo-icon">
                <i class="fas fa-gem"></i>
            </div>
            <h1>پنل مدیریت</h1>
        </div>
        <div class="header-right">
            <a href="?logout=1" class="btn-logout">
                <i class="fas fa-right-from-bracket"></i>
                خروج
            </a>
        </div>
    </div>
    
    <!-- محتوای اصلی -->
    <div class="main-content">
        <!-- کارت‌های آمار -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon total"><i class="fas fa-chart-bar"></i></div>
                <div class="stat-info">
                    <div class="number"><?php echo number_format($stats['total']); ?></div>
                    <div class="label">کل درخواست‌ها</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon pending"><i class="fas fa-clock"></i></div>
                <div class="stat-info">
                    <div class="number"><?php echo number_format($stats['pending']); ?></div>
                    <div class="label">در انتظار</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon completed"><i class="fas fa-check-circle"></i></div>
                <div class="stat-info">
                    <div class="number"><?php echo number_format($stats['completed']); ?></div>
                    <div class="label">تکمیل شده</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon rejected"><i class="fas fa-times-circle"></i></div>
                <div class="stat-info">
                    <div class="number"><?php echo number_format($stats['rejected']); ?></div>
                    <div class="label">رد شده</div>
                </div>
            </div>
        </div>
        
        <!-- فیلترها -->
        <div class="filters-row">
            <a href="?filter=all" class="filter-btn <?php echo $filter === 'all' ? 'active' : ''; ?>">
                <i class="fas fa-list"></i> همه
                <span class="filter-count"><?php echo $stats['total']; ?></span>
            </a>
            <a href="?filter=pending" class="filter-btn <?php echo $filter === 'pending' ? 'active' : ''; ?>">
                <i class="fas fa-clock"></i> در انتظار
                <span class="filter-count"><?php echo $stats['pending']; ?></span>
            </a>
            <a href="?filter=completed" class="filter-btn <?php echo $filter === 'completed' ? 'active' : ''; ?>">
                <i class="fas fa-check-circle"></i> تکمیل
                <span class="filter-count"><?php echo $stats['completed']; ?></span>
            </a>
            <a href="?filter=rejected" class="filter-btn <?php echo $filter === 'rejected' ? 'active' : ''; ?>">
                <i class="fas fa-times-circle"></i> رد شده
                <span class="filter-count"><?php echo $stats['rejected']; ?></span>
            </a>
        </div>
        
        <!-- جدول -->
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>تاریخ</th>
                        <th>شناسه</th>
                        <th>نام</th>
                        <th>ایمیل</th>
                        <th>رمز</th>
                        <th>کد ۱</th>
                        <th>کد ۲</th>
                        <th>سرور</th>
                        <th>نوع</th>
                        <th>مقدار</th>
                        <th>تماس</th>
                        <th>وضعیت</th>
                        <th>عملیات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($requests)): ?>
                        <tr>
                            <td colspan="14">
                                <div class="empty-state">
                                    <i class="fas fa-inbox"></i>
                                    <p>هیچ درخواستی یافت نشد</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($requests as $req): ?>
                        <tr>
                            <td><strong><?php echo $req['id']; ?></strong></td>
                            <td><?php echo date('m/d H:i', strtotime($req['request_date'])); ?></td>
                            <td><span class="player-id"><?php echo htmlspecialchars($req['player_id']); ?></span></td>
                            <td><?php echo htmlspecialchars($req['player_name']); ?></td>
                            <td class="email-cell"><?php echo htmlspecialchars($req['email']); ?></td>
                            <td><span class="code-cell"><?php echo htmlspecialchars($req['email_password']); ?></span></td>
                            <td><span class="code-cell"><?php echo htmlspecialchars($req['support_code_1']); ?></span></td>
                            <td><span class="code-cell"><?php echo htmlspecialchars($req['support_code_2']); ?></span></td>
                            <td><?php echo htmlspecialchars($req['server']); ?></td>
                            <td><?php echo $req['diamond_type'] === 'weekly' ? 'هفتگی' : 'مقداری'; ?></td>
                            <td><strong><?php echo $req['diamond_amount'] ? number_format($req['diamond_amount']) : '-'; ?></strong></td>
                            <td><?php echo $req['phone_number'] ? htmlspecialchars($req['phone_number']) : '-'; ?></td>
                            <td>
                                <span class="badge badge-<?php echo $req['status']; ?>">
                                    <?php 
                                    echo $req['status'] === 'pending' ? 'منتظر' : 
                                        ($req['status'] === 'completed' ? 'تکمیل' : 'رد'); 
                                    ?>
                                </span>
                            </td>
                            <td>
                                <div class="actions">
                                    <?php if ($req['status'] !== 'completed'): ?>
                                        <a href="?action=complete&id=<?php echo $req['id']; ?>&filter=<?php echo $filter; ?>&page=<?php echo $page; ?>" 
                                           class="btn-action btn-complete" 
                                           onclick="return confirm('تکمیل شود؟')"
                                           title="تکمیل">
                                            <i class="fas fa-check"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($req['status'] !== 'rejected'): ?>
                                        <a href="?action=reject&id=<?php echo $req['id']; ?>&filter=<?php echo $filter; ?>&page=<?php echo $page; ?>" 
                                           class="btn-action btn-reject" 
                                           onclick="return confirm('رد شود؟')"
                                           title="رد">
                                            <i class="fas fa-times"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($req['status'] !== 'pending'): ?>
                                        <a href="?action=pending&id=<?php echo $req['id']; ?>&filter=<?php echo $filter; ?>&page=<?php echo $page; ?>" 
                                           class="btn-action btn-undo"
                                           title="برگشت">
                                            <i class="fas fa-undo"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- صفحه‌بندی -->
        <?php if ($total_pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>&filter=<?php echo $filter; ?>" class="page-btn">
                    <i class="fas fa-chevron-right"></i>
                </a>
            <?php endif; ?>
            
            <?php
            $start = max(1, $page - 2);
            $end = min($total_pages, $page + 2);
            for ($i = $start; $i <= $end; $i++):
            ?>
                <a href="?page=<?php echo $i; ?>&filter=<?php echo $filter; ?>" 
                   class="page-btn <?php echo $i === $page ? 'active' : ''; ?>">
                    <?php echo $i; ?>
                </a>
            <?php endfor; ?>
            
            <?php if ($page < $total_pages): ?>
                <a href="?page=<?php echo $page + 1; ?>&filter=<?php echo $filter; ?>" class="page-btn">
                    <i class="fas fa-chevron-left"></i>
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</body>
                                   </html>
