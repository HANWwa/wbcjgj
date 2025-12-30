/**
 * 管理后台JavaScript
 * @神奇奶酪
 */

// 页面加载完成后执行
document.addEventListener('DOMContentLoaded', function() {
    // 初始化侧边栏
    initSidebar();

    // 初始化表格
    initTables();
});

/**
 * 初始化侧边栏
 */
function initSidebar() {
    // 移动端菜单切换
    const toggleBtn = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.admin-sidebar');

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', function() {
            sidebar.classList.toggle('show');
        });

        // 点击外部关闭侧边栏
        document.addEventListener('click', function(e) {
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
                    sidebar.classList.remove('show');
                }
            }
        });
    }
}

/**
 * 初始化表格
 */
function initTables() {
    // 表格行点击效果
    const tableRows = document.querySelectorAll('.data-table tbody tr');

    tableRows.forEach(function(row) {
        row.addEventListener('mouseenter', function() {
            this.style.transform = 'scale(1.01)';
        });

        row.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });
    });
}

/**
 * 显示确认对话框
 */
function confirmAction(message) {
    return confirm(message || '确定要执行此操作吗？');
}

/**
 * 格式化数字
 */
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

/**
 * 格式化日期
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    const hour = String(date.getHours()).padStart(2, '0');
    const minute = String(date.getMinutes()).padStart(2, '0');

    return `${year}-${month}-${day} ${hour}:${minute}`;
}

/**
 * 复制文本到剪贴板
 */
function copyToClipboard(text) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(function() {
            showSuccess('已复制到剪贴板');
        }).catch(function() {
            fallbackCopy(text);
        });
    } else {
        fallbackCopy(text);
    }
}

/**
 * 降级复制方法
 */
function fallbackCopy(text) {
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();

    try {
        document.execCommand('copy');
        showSuccess('已复制到剪贴板');
    } catch (err) {
        showError('复制失败，请手动复制');
    }

    document.body.removeChild(textarea);
}

/**
 * 防抖函数
 */
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

/**
 * 节流函数
 */
function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

/**
 * 显示加载中
 */
function showLoading(message) {
    const loadingHTML = `
        <div id="loadingOverlay" style="
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        ">
            <div style="
                background: var(--card-bg);
                padding: 30px 50px;
                border-radius: 15px;
                text-align: center;
                box-shadow: var(--shadow-xl);
            ">
                <div style="
                    width: 50px;
                    height: 50px;
                    border: 4px solid rgba(255, 255, 255, 0.1);
                    border-top-color: var(--primary-color);
                    border-radius: 50%;
                    animation: spin 1s linear infinite;
                    margin: 0 auto 20px;
                "></div>
                <div style="color: var(--text-light); font-size: 16px;">
                    ${message || '加载中...'}
                </div>
            </div>
        </div>
    `;

    // 移除旧的加载层
    const oldLoading = document.getElementById('loadingOverlay');
    if (oldLoading) {
        oldLoading.remove();
    }

    document.body.insertAdjacentHTML('beforeend', loadingHTML);
}

/**
 * 隐藏加载中
 */
function hideLoading() {
    const loading = document.getElementById('loadingOverlay');
    if (loading) {
        loading.remove();
    }
}

/**
 * 导出数据为CSV
 */
function exportToCSV(data, filename) {
    if (!data || !data.length) {
        showError('没有可导出的数据');
        return;
    }

    // 获取表头
    const headers = Object.keys(data[0]);
    let csv = headers.join(',') + '\n';

    // 添加数据行
    data.forEach(function(row) {
        const values = headers.map(function(header) {
            const value = row[header];
            // 处理包含逗号的值
            return typeof value === 'string' && value.includes(',')
                ? `"${value}"`
                : value;
        });
        csv += values.join(',') + '\n';
    });

    // 创建下载链接
    const blob = new Blob(['\ufeff' + csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);

    link.setAttribute('href', url);
    link.setAttribute('download', filename || 'export.csv');
    link.style.visibility = 'hidden';

    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);

    showSuccess('导出成功');
}

/**
 * 打印页面
 */
function printPage() {
    window.print();
}

/**
 * 刷新页面
 */
function refreshPage() {
    location.reload();
}

/**
 * 返回上一页
 */
function goBack() {
    history.back();
}

/**
 * 跳转到指定URL
 */
function navigateTo(url) {
    window.location.href = url;
}

/**
 * 在新窗口打开URL
 */
function openInNewTab(url) {
    window.open(url, '_blank');
}

/**
 * 获取URL参数
 */
function getUrlParameter(name) {
    name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
    const regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
    const results = regex.exec(location.search);
    return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
}

/**
 * 设置URL参数
 */
function setUrlParameter(key, value) {
    const url = new URL(window.location);
    url.searchParams.set(key, value);
    window.history.pushState({}, '', url);
}

/**
 * 验证邮箱
 */
function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

/**
 * 验证URL
 */
function isValidUrl(url) {
    try {
        new URL(url);
        return true;
    } catch (e) {
        return false;
    }
}

/**
 * 转义HTML
 */
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) { return map[m]; });
}

/**
 * 时间格式化
 */
function timeAgo(dateString) {
    const now = new Date();
    const past = new Date(dateString);
    const diff = Math.floor((now - past) / 1000);

    if (diff < 60) {
        return '刚刚';
    } else if (diff < 3600) {
        return Math.floor(diff / 60) + '分钟前';
    } else if (diff < 86400) {
        return Math.floor(diff / 3600) + '小时前';
    } else if (diff < 2592000) {
        return Math.floor(diff / 86400) + '天前';
    } else {
        return formatDate(dateString);
    }
}
