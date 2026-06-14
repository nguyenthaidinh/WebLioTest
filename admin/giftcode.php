<?php
include_once 'set.php';
include_once 'connect.php';
if ($_login == null) {
    header("Location: /app/login.php");
    exit();
}

$_alert = '';

// Xử lý xóa gift code
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $stmt = $conn->prepare("DELETE FROM giftcode WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        $_alert = '<div class="alert alert-success">Đã xóa Gift Code thành công!</div>';
    } else {
        $_alert = '<div class="alert alert-danger">Lỗi khi xóa: ' . $conn->error . '</div>';
    }
    $stmt->close();
}

// Xử lý tạo gift code mới
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['create_giftcode'])) {
    $code = trim($_POST['code']);
    $count_left = intval($_POST['count_left']);
    $expired = $_POST['expired'];
    
    // Build detail JSON từ form
    $items = [];
    if (isset($_POST['item_id']) && is_array($_POST['item_id'])) {
        for ($i = 0; $i < count($_POST['item_id']); $i++) {
            $item_id = intval($_POST['item_id'][$i]);
            $item_qty = intval($_POST['item_qty'][$i]);
            if ($item_id > 0 && $item_qty > 0) {
                $items[] = [
                    'id' => $item_id,
                    'quantity' => $item_qty,
                    'options' => [['id' => 30, 'param' => 0]]
                ];
            }
        }
    }
    
    if (empty($code)) {
        $_alert = '<div class="alert alert-warning">Vui lòng nhập mã Gift Code!</div>';
    } elseif (empty($items)) {
        $_alert = '<div class="alert alert-warning">Vui lòng thêm ít nhất 1 vật phẩm!</div>';
    } else {
        $detail = json_encode($items, JSON_PRETTY_PRINT);
        
        // Check trùng code
        $check = $conn->prepare("SELECT id FROM giftcode WHERE code = ?");
        $check->bind_param("s", $code);
        $check->execute();
        $check->store_result();
        
        if ($check->num_rows > 0) {
            $_alert = '<div class="alert alert-warning">Mã Gift Code đã tồn tại!</div>';
        } else {
            $stmt = $conn->prepare("INSERT INTO giftcode (code, count_left, detail, expired) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("siss", $code, $count_left, $detail, $expired);
            if ($stmt->execute()) {
                $_alert = '<div class="alert alert-success">Tạo Gift Code <strong>' . htmlspecialchars($code) . '</strong> thành công!</div>';
            } else {
                $_alert = '<div class="alert alert-danger">Lỗi: ' . $conn->error . '</div>';
            }
            $stmt->close();
        }
        $check->close();
    }
}

// Lấy danh sách gift code
$giftcodes = [];
$result = $conn->query("SELECT * FROM giftcode ORDER BY datecreate DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $giftcodes[] = $row;
    }
}

// Lấy danh sách vật phẩm từ item_template
$all_items = [];
$item_map = []; // id => name mapping
$item_result = $conn->query("SELECT id, NAME as name FROM item_template ORDER BY id ASC");
if ($item_result) {
    while ($row = $item_result->fetch_assoc()) {
        $all_items[] = $row;
        $item_map[$row['id']] = $row['name'];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Quản Lý Gift Code - Code by Lio</title>
    <link href="../assets/bootstrap/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <script src="../assets/jquery/jquery.min.js"></script>
    <link rel="icon" href="../image/icon.png?v=99">
    <link href="../assets/main.css" rel="stylesheet">
    <style>
        body { background: #1a1a2e; color: #e0e0e0; }
        .admin-header {
            background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%);
            border-bottom: 2px solid rgba(249,115,22,0.4);
            padding: 12px 0;
        }
        .admin-header a { color: #febb12 !important; font-weight: 600; text-decoration: none; }
        .admin-header a:hover { color: #f97316 !important; }
        
        .page-title {
            background: linear-gradient(135deg, #f97316, #febb12);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 800;
            font-size: 28px;
            text-align: center;
            margin: 30px 0 10px;
        }
        .page-subtitle {
            text-align: center;
            color: #9ca3af;
            font-size: 14px;
            margin-bottom: 30px;
        }
        
        /* Card styles */
        .gc-card {
            background: rgba(30, 30, 60, 0.8);
            border: 1px solid rgba(249,115,22,0.2);
            border-radius: 16px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            backdrop-filter: blur(10px);
        }
        .gc-card h5 {
            color: #febb12;
            font-weight: 700;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(249,115,22,0.15);
        }
        .gc-card h5 i { margin-right: 8px; }
        
        /* Form styles */
        .form-control {
            background: rgba(15, 15, 40, 0.8) !important;
            border: 1px solid rgba(249,115,22,0.2) !important;
            color: #e0e0e0 !important;
            border-radius: 8px !important;
        }
        .form-control:focus {
            border-color: #f97316 !important;
            box-shadow: 0 0 0 0.2rem rgba(249,115,22,0.15) !important;
        }
        .form-control::placeholder { color: #6b7280 !important; }
        label { color: #d1d5db; font-weight: 600; font-size: 13px; margin-bottom: 4px; }
        
        /* Buttons */
        .btn-create {
            background: linear-gradient(135deg, #f97316 0%, #ea580c 100%);
            color: #fff;
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 700;
            font-size: 15px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(249,115,22,0.3);
        }
        .btn-create:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(249,115,22,0.5);
            color: #fff;
        }
        .btn-add-item {
            background: rgba(34,197,94,0.15);
            color: #4ade80;
            border: 1px dashed rgba(34,197,94,0.4);
            border-radius: 8px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        .btn-add-item:hover {
            background: rgba(34,197,94,0.25);
            color: #86efac;
        }
        .btn-remove-item {
            background: rgba(239,68,68,0.15);
            color: #f87171;
            border: none;
            border-radius: 6px;
            padding: 6px 12px;
            font-size: 12px;
            transition: all 0.2s ease;
        }
        .btn-remove-item:hover { background: rgba(239,68,68,0.3); }
        
        /* Item row */
        .item-row {
            background: rgba(15, 15, 40, 0.5);
            border: 1px solid rgba(249,115,22,0.1);
            border-radius: 10px;
            padding: 12px;
            margin-bottom: 8px;
            display: flex;
            gap: 10px;
            align-items: center;
            animation: slideIn 0.3s ease;
        }
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-10px); }
            to { opacity: 1; transform: translateX(0); }
        }
        .item-row .form-control { font-size: 13px; padding: 6px 10px; }
        .item-row label { font-size: 11px; margin-bottom: 2px; }
        
        /* Table */
        .gc-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 6px;
        }
        .gc-table thead th {
            background: rgba(249,115,22,0.1);
            color: #febb12;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 12px 10px;
            border: none;
        }
        .gc-table thead th:first-child { border-radius: 8px 0 0 8px; }
        .gc-table thead th:last-child { border-radius: 0 8px 8px 0; }
        .gc-table tbody tr {
            background: rgba(20, 20, 50, 0.6);
            transition: all 0.2s ease;
        }
        .gc-table tbody tr:hover { background: rgba(249,115,22,0.08); }
        .gc-table tbody td {
            padding: 10px;
            border: none;
            font-size: 13px;
            vertical-align: middle;
        }
        .gc-table tbody td:first-child { border-radius: 8px 0 0 8px; }
        .gc-table tbody td:last-child { border-radius: 0 8px 8px 0; }
        
        .code-badge {
            background: linear-gradient(135deg, #f97316, #febb12);
            color: #000;
            font-weight: 800;
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-family: monospace;
            letter-spacing: 1px;
        }
        .count-badge {
            background: rgba(34,197,94,0.15);
            color: #4ade80;
            padding: 3px 10px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 12px;
        }
        .expired-text { color: #9ca3af; font-size: 12px; }
        .expired-text.is-expired { color: #ef4444; }
        
        .btn-del {
            background: rgba(239,68,68,0.15);
            color: #f87171;
            border: 1px solid rgba(239,68,68,0.2);
            border-radius: 6px;
            padding: 5px 12px;
            font-size: 11px;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        .btn-del:hover {
            background: rgba(239,68,68,0.3);
            color: #fca5a5;
        }
        
        .detail-preview {
            max-width: 250px;
            font-size: 11px;
            color: #9ca3af;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            cursor: pointer;
        }
        .detail-preview:hover { color: #febb12; }
        
        /* Footer */
        .admin-footer {
            text-align: center;
            padding: 20px;
            margin-top: 40px;
            border-top: 1px solid rgba(249,115,22,0.1);
        }
        .lio-badge-footer {
            background: linear-gradient(135deg, #f97316, #febb12);
            color: #000;
            font-weight: 800;
            padding: 2px 10px;
            border-radius: 4px;
            font-size: 10px;
            text-transform: uppercase;
            display: inline-block;
        }
        
        /* Alert overrides */
        .alert { border-radius: 10px; font-size: 14px; }
        .alert-success { background: rgba(34,197,94,0.15); color: #4ade80; border: 1px solid rgba(34,197,94,0.3); }
        .alert-danger { background: rgba(239,68,68,0.15); color: #f87171; border: 1px solid rgba(239,68,68,0.3); }
        .alert-warning { background: rgba(234,179,8,0.15); color: #fbbf24; border: 1px solid rgba(234,179,8,0.3); }
        
        /* Stats */
        .stats-row { display: flex; gap: 15px; margin-bottom: 25px; flex-wrap: wrap; }
        .stat-box {
            flex: 1;
            min-width: 120px;
            background: rgba(30,30,60,0.6);
            border: 1px solid rgba(249,115,22,0.15);
            border-radius: 12px;
            padding: 15px;
            text-align: center;
        }
        .stat-box .stat-num {
            font-size: 28px;
            font-weight: 800;
            background: linear-gradient(135deg, #f97316, #febb12);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .stat-box .stat-label { font-size: 11px; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 4px; }
        
        /* Searchable select */
        .item-select-wrap { position: relative; flex: 2; }
        .item-search-input {
            background: rgba(15, 15, 40, 0.8) !important;
            border: 1px solid rgba(249,115,22,0.2) !important;
            color: #e0e0e0 !important;
            border-radius: 8px !important;
            padding: 6px 10px;
            font-size: 13px;
            width: 100%;
            box-sizing: border-box;
        }
        .item-search-input:focus {
            border-color: #f97316 !important;
            box-shadow: 0 0 0 0.2rem rgba(249,115,22,0.15) !important;
            outline: none;
        }
        .item-dropdown {
            display: none;
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            max-height: 250px;
            overflow-y: auto;
            background: rgba(20, 20, 50, 0.98);
            border: 1px solid rgba(249,115,22,0.3);
            border-radius: 8px;
            margin-top: 2px;
            z-index: 1000;
            box-shadow: 0 8px 24px rgba(0,0,0,0.5);
        }
        .item-dropdown.show { display: block; }
        .item-option {
            padding: 8px 12px;
            cursor: pointer;
            font-size: 12px;
            color: #d1d5db;
            border-bottom: 1px solid rgba(249,115,22,0.05);
            transition: background 0.15s ease;
        }
        .item-option:hover { background: rgba(249,115,22,0.15); color: #febb12; }
        .item-option .item-id { color: #f97316; font-weight: 700; margin-right: 6px; font-family: monospace; }
        .item-option .item-name { color: #e0e0e0; }
        .item-dropdown::-webkit-scrollbar { width: 6px; }
        .item-dropdown::-webkit-scrollbar-track { background: rgba(15,15,40,0.5); border-radius: 3px; }
        .item-dropdown::-webkit-scrollbar-thumb { background: rgba(249,115,22,0.4); border-radius: 3px; }
        .item-dropdown::-webkit-scrollbar-thumb:hover { background: rgba(249,115,22,0.6); }
        
        .selected-item-badge {
            display: inline-block;
            background: rgba(249,115,22,0.12);
            color: #febb12;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 4px;
            margin-top: 3px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .item-row { flex-wrap: wrap; }
            .gc-table { font-size: 11px; }
        }
    </style>
</head>
<body>
    <div class="admin-header">
        <div class="container">
            <a href="/admin"><i class="fas fa-arrow-left"></i> Quay lại menu admin</a>
        </div>
    </div>

    <div class="container" style="max-width: 900px;">
        <div class="page-title"><i class="fas fa-gift"></i> QUẢN LÝ GIFT CODE</div>
        <div class="page-subtitle">Tạo và quản lý mã quà tặng cho người chơi</div>

        <?php echo $_alert; ?>

        <!-- Stats -->
        <?php
        $total_codes = count($giftcodes);
        $active_codes = 0;
        $total_uses = 0;
        foreach ($giftcodes as $gc) {
            if (strtotime($gc['expired']) > time()) $active_codes++;
            $total_uses += $gc['count_left'];
        }
        ?>
        <div class="stats-row">
            <div class="stat-box">
                <div class="stat-num"><?php echo $total_codes; ?></div>
                <div class="stat-label">Tổng Gift Code</div>
            </div>
            <div class="stat-box">
                <div class="stat-num"><?php echo $active_codes; ?></div>
                <div class="stat-label">Còn hiệu lực</div>
            </div>
            <div class="stat-box">
                <div class="stat-num"><?php echo number_format($total_uses); ?></div>
                <div class="stat-label">Tổng lượt dùng</div>
            </div>
        </div>

        <!-- Form tạo Gift Code -->
        <div class="gc-card">
            <h5><i class="fas fa-plus-circle"></i> Tạo Gift Code Mới</h5>
            <form method="POST">
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label>Mã Gift Code</label>
                        <input type="text" class="form-control" name="code" placeholder="VD: tanthu2024" required>
                    </div>
                    <div class="col-md-4">
                        <label>Số lượt sử dụng</label>
                        <input type="number" class="form-control" name="count_left" value="999" min="1" required>
                    </div>
                    <div class="col-md-4">
                        <label>Ngày hết hạn</label>
                        <input type="datetime-local" class="form-control" name="expired" value="2030-01-01T00:00" required>
                    </div>
                </div>
                
                <label class="mb-2">Danh sách vật phẩm <small style="color:#6b7280;">(gõ tên hoặc ID để tìm)</small></label>
                <div id="items-container">
                    <div class="item-row">
                        <div class="item-select-wrap">
                            <label>Vật phẩm</label>
                            <input type="hidden" name="item_id[]" class="item-id-hidden" required>
                            <input type="text" class="item-search-input" placeholder="Gõ tên hoặc ID vật phẩm..." autocomplete="off" onfocus="showDropdown(this)" oninput="filterItems(this)">
                            <div class="item-dropdown"></div>
                            <div class="selected-item-badge" style="display:none;"></div>
                        </div>
                        <div style="flex:1;">
                            <label>Số lượng</label>
                            <input type="number" class="form-control" name="item_qty[]" placeholder="VD: 50" min="1" required>
                        </div>
                        <div style="padding-top:20px;">
                            <button type="button" class="btn-remove-item" onclick="removeItem(this)"><i class="fas fa-times"></i></button>
                        </div>
                    </div>
                </div>
                <button type="button" class="btn-add-item mt-2 mb-3" onclick="addItem()">
                    <i class="fas fa-plus"></i> Thêm vật phẩm
                </button>
                <br>
                <button type="submit" name="create_giftcode" class="btn-create">
                    <i class="fas fa-check-circle"></i> Tạo Gift Code
                </button>
            </form>
        </div>

        <!-- Danh sách Gift Code -->
        <div class="gc-card">
            <h5><i class="fas fa-list"></i> Danh Sách Gift Code (<?php echo $total_codes; ?>)</h5>
            <?php if (empty($giftcodes)): ?>
                <p style="text-align:center;color:#6b7280;">Chưa có Gift Code nào.</p>
            <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="gc-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Mã Code</th>
                            <th>Lượt còn</th>
                            <th>Vật phẩm</th>
                            <th>Ngày tạo</th>
                            <th>Hết hạn</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($giftcodes as $gc):
                            $is_expired = strtotime($gc['expired']) < time();
                            $detail_items = json_decode($gc['detail'], true);
                            $item_summary = '';
                            if (is_array($detail_items)) {
                                $parts = [];
                                foreach ($detail_items as $di) {
                                    $iname = isset($item_map[$di['id']]) ? $item_map[$di['id']] : '???';
                                    $parts[] = $iname . ' x' . number_format($di['quantity']);
                                }
                                $item_summary = implode(', ', $parts);
                            }
                        ?>
                        <tr>
                            <td style="color:#6b7280;"><?php echo $gc['id']; ?></td>
                            <td><span class="code-badge"><?php echo htmlspecialchars($gc['code']); ?></span></td>
                            <td><span class="count-badge"><?php echo number_format($gc['count_left']); ?></span></td>
                            <td>
                                <div class="detail-preview" title="<?php echo htmlspecialchars($item_summary); ?>">
                                    <?php echo htmlspecialchars($item_summary); ?>
                                </div>
                            </td>
                            <td class="expired-text"><?php echo date('d/m/Y H:i', strtotime($gc['datecreate'])); ?></td>
                            <td class="expired-text <?php echo $is_expired ? 'is-expired' : ''; ?>">
                                <?php echo $is_expired ? '⛔ ' : '✅ '; ?>
                                <?php echo date('d/m/Y', strtotime($gc['expired'])); ?>
                            </td>
                            <td>
                                <a href="?delete_id=<?php echo $gc['id']; ?>" class="btn-del" onclick="return confirm('Xóa Gift Code: <?php echo htmlspecialchars($gc['code']); ?>?');">
                                    <i class="fas fa-trash"></i> Xóa
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Footer -->
        <div class="admin-footer">
            <small style="color:#6b7280;">IP: <?php echo $_IP; ?></small><br>
            <span class="lio-badge-footer">Code by Lio</span>
        </div>
    </div>

    <script>
    // Item data from PHP
    var ALL_ITEMS = <?php echo json_encode($all_items); ?>;

    function showDropdown(input) {
        var dropdown = input.parentElement.querySelector('.item-dropdown');
        populateDropdown(dropdown, input.value);
        dropdown.classList.add('show');
    }

    function filterItems(input) {
        var dropdown = input.parentElement.querySelector('.item-dropdown');
        populateDropdown(dropdown, input.value);
        dropdown.classList.add('show');
    }

    function populateDropdown(dropdown, query) {
        query = query.toLowerCase().trim();
        var html = '';
        var count = 0;
        for (var i = 0; i < ALL_ITEMS.length; i++) {
            var item = ALL_ITEMS[i];
            var idStr = String(item.id);
            var nameStr = item.name.toLowerCase();
            if (query === '' || nameStr.indexOf(query) !== -1 || idStr.indexOf(query) !== -1) {
                html += '<div class="item-option" data-id="' + item.id + '" data-name="' + item.name.replace(/"/g, '&quot;') + '" onclick="selectItem(this)">';
                html += '<span class="item-id">#' + item.id + '</span>';
                html += '<span class="item-name">' + item.name + '</span>';
                html += '</div>';
                count++;
                if (count >= 50) break; // Limit hiển thị 50 item
            }
        }
        if (count === 0) {
            html = '<div style="padding:12px;color:#6b7280;text-align:center;font-size:12px;">Không tìm thấy vật phẩm</div>';
        }
        dropdown.innerHTML = html;
    }

    function selectItem(option) {
        var wrap = option.closest('.item-select-wrap');
        var hiddenInput = wrap.querySelector('.item-id-hidden');
        var searchInput = wrap.querySelector('.item-search-input');
        var badge = wrap.querySelector('.selected-item-badge');
        var dropdown = wrap.querySelector('.item-dropdown');
        
        var id = option.getAttribute('data-id');
        var name = option.getAttribute('data-name');
        
        hiddenInput.value = id;
        searchInput.value = '#' + id + ' - ' + name;
        badge.textContent = '✅ ID: ' + id + ' | ' + name;
        badge.style.display = 'inline-block';
        dropdown.classList.remove('show');
    }

    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.classList.contains('item-search-input')) {
            document.querySelectorAll('.item-dropdown').forEach(function(d) {
                d.classList.remove('show');
            });
        }
    });

    function addItem() {
        var container = document.getElementById('items-container');
        var row = document.createElement('div');
        row.className = 'item-row';
        row.innerHTML = `
            <div class="item-select-wrap">
                <label>Vật phẩm</label>
                <input type="hidden" name="item_id[]" class="item-id-hidden" required>
                <input type="text" class="item-search-input" placeholder="Gõ tên hoặc ID vật phẩm..." autocomplete="off" onfocus="showDropdown(this)" oninput="filterItems(this)">
                <div class="item-dropdown"></div>
                <div class="selected-item-badge" style="display:none;"></div>
            </div>
            <div style="flex:1;">
                <label>Số lượng</label>
                <input type="number" class="form-control" name="item_qty[]" placeholder="VD: 50" min="1" required>
            </div>
            <div style="padding-top:20px;">
                <button type="button" class="btn-remove-item" onclick="removeItem(this)"><i class="fas fa-times"></i></button>
            </div>
        `;
        container.appendChild(row);
    }
    
    function removeItem(btn) {
        var container = document.getElementById('items-container');
        if (container.children.length > 1) {
            btn.closest('.item-row').remove();
        } else {
            alert('Cần ít nhất 1 vật phẩm!');
        }
    }
    </script>
</body>
</html>
