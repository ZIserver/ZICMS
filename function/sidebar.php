<?php
/**
 * 动态侧边栏组件
 * 调用方式：在需要显示侧边栏的位置调用 <?php include_once __DIR__.'/includes/sidebar.inc.php'; ?>
 */
class DynamicSidebar {
    private $pdo;
    private $currentPath;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    }

    public function render() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM system_sidebar 
                WHERE is_show = 1 
                AND (permission_tag IS NULL OR permission_tag IN (
                    SELECT perm_tag FROM user_permissions 
                    WHERE user_id = :user_id
                ))
                ORDER BY parent_id, sort_order ASC
            ");
            $stmt->execute([':user_id' => $_SESSION['user']['id'] ?? 0]);
            $menuItems = $stmt->fetchAll(PDO::FETCH_OBJ);
            
            echo $this->buildMenuTree($menuItems);
            
        } catch (PDOException $e) {
            error_log("Sidebar Error: " . $e->getMessage());
            echo '<!-- Sidebar temporarily unavailable -->';
        }
    }

    private function buildMenuTree($items, $parentId = 0) {
        $html = '<ul class="sys-sidebar-menu">';
        foreach ($items as $item) {
            if ($item->parent_id == $parentId) {
                $isActive = $this->isMenuItemActive($item->menu_path);
                $html .= sprintf(
                    '<li class="%s %s">',
                    $isActive ? 'active' : '',
                    $this->hasChildren($items, $item->menu_id) ? 'has-submenu' : ''
                );
                $html .= sprintf(
                    '<a href="%s" %s>%s%s</a>',
                    htmlspecialchars($item->menu_path),
                    $item->menu_path === '#' ? 'class="disabled"' : '',
                    $item->menu_icon ? '<i class="'.$item->menu_icon.'"></i>' : '',
                    htmlspecialchars($item->menu_title)
                );
                $html .= $this->buildMenuTree($items, $item->menu_id);
                $html .= '</li>';
            }
        }
        $html .= '</ul>';
        return $html;
    }

    private function hasChildren($items, $id) {
        foreach ($items as $item) {
            if ($item->parent_id == $id) return true;
        }
        return false;
    }

    private function isMenuItemActive($path) {
        return strpos($this->currentPath, $path) === 0 ? 'active' : '';
    }
}

// 初始化并输出侧边栏（需要提前初始化数据库连接）
if (isset($pdo)) {
    $sidebar = new DynamicSidebar($pdo);
    $sidebar->render();
}
?>
