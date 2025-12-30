/**
 * 免费抽奖JavaScript
 * @神奇奶酪
 */

let currentStep = 1;
let selectedLotteryType = '';
let weiboCookie = '';
let weiboData = null;

/**
 * 验证Cookie
 */
function validateCookie() {
    const cookie = document.getElementById('weiboCookie').value.trim();

    if (!cookie) {
        showError('请输入Cookie');
        return;
    }

    // 简单格式验证
    if (!cookie.includes('SUB=') && !cookie.includes('SUBP=')) {
        showError('Cookie格式不正确，请确保包含完整的Cookie信息');
        return;
    }

    // 保存Cookie
    weiboCookie = cookie;

    // 验证Cookie
    ajax('ajax_lottery_free.php', {
        action: 'validate_cookie',
        cookie: cookie
    }, function(result) {
        if (result.success) {
            showSuccess('Cookie验证成功！');
            goToStep(3);
        } else {
            showError(result.message);
        }
    });
}

/**
 * 开始免费抽奖
 */
function startFreeLottery() {
    if (!selectedLotteryType) {
        showError('请选择抽奖类型');
        return;
    }

    const weiboUrl = document.getElementById('weiboUrl').value.trim();
    const winnerCount = parseInt(document.getElementById('winnerCount').value);

    if (!weiboUrl) {
        showError('请输入微博链接');
        return;
    }

    if (!weiboCookie) {
        showError('请先配置Cookie');
        return;
    }

    if (!winnerCount || winnerCount < 1 || winnerCount > 50) {
        showError('请输入有效的中奖人数（1-50）');
        return;
    }

    // 显示抽奖动画
    goToStep(5);

    // 开始抽奖动画
    startLotteryAnimation();

    // 发送抽奖请求
    ajax('ajax_lottery_free.php', {
        action: 'start_lottery_free',
        weibo_url: weiboUrl,
        cookie: weiboCookie,
        lottery_type: selectedLotteryType,
        winner_count: winnerCount
    }, function(result) {
        if (result.success) {
            // 延迟显示结果，让动画播放完整
            setTimeout(function() {
                showLotteryResult(result.data);
                goToStep(6);
            }, 3000);
        } else {
            stopLotteryAnimation();
            showError(result.message);
            goToStep(4);
        }
    });
}

/**
 * 显示抽奖结果
 */
function showLotteryResult(data) {
    // 停止动画
    stopLotteryAnimation();

    // 显示基本信息
    document.getElementById('verifyCode').textContent = data.verify_code;
    document.getElementById('totalParticipants').textContent = data.total_participants;
    document.getElementById('totalWinners').textContent = data.winners.length;

    // 显示中奖名单
    const winnersList = document.getElementById('winnersList');
    let winnersHTML = '';

    data.winners.forEach(function(winner, index) {
        winnersHTML += `
            <div class="winner-card">
                <div class="winner-rank">${index + 1}</div>
                <div class="winner-info">
                    <div class="winner-name">@${winner.name}</div>
                    <div class="winner-uid">UID: ${winner.uid}</div>
                </div>
            </div>
        `;
    });

    winnersList.innerHTML = winnersHTML;

    // 显示公告
    document.getElementById('announcement').value = data.announcement;
}

/**
 * 复制公告
 */
function copyAnnouncement() {
    const announcement = document.getElementById('announcement');
    announcement.select();
    document.execCommand('copy');
    showSuccess('公告已复制到剪贴板');
}

/**
 * 重新抽奖
 */
function resetLottery() {
    currentStep = 1;
    selectedLotteryType = '';
    weiboCookie = '';
    weiboData = null;

    document.getElementById('weiboUrl').value = '';
    document.getElementById('weiboCookie').value = '';
    document.getElementById('winnerCount').value = '1';

    // 移除所有类型卡片的选中状态
    document.querySelectorAll('.type-card').forEach(function(card) {
        card.classList.remove('selected');
    });

    goToStep(1);
}

/**
 * 切换到指定步骤
 */
function goToStep(step) {
    // 验证步骤切换
    if (step === 3 && currentStep === 1) {
        // 从步骤1直接跳到步骤3，需要验证URL
        const weiboUrl = document.getElementById('weiboUrl').value.trim();
        if (!weiboUrl) {
            showError('请输入微博链接');
            return;
        }
        if (!weiboUrl.includes('weibo.com') && !weiboUrl.includes('weibo.cn') && !weiboUrl.includes('t.cn')) {
            showError('请输入有效的微博链接');
            return;
        }
    }

    if (step === 4 && currentStep === 3) {
        // 从步骤3到步骤4，需要验证抽奖类型
        if (!selectedLotteryType) {
            showError('请选择抽奖类型');
            return;
        }

        // 更新确认信息
        updateConfirmInfo();
    }

    currentStep = step;

    // 更新步骤指示器
    document.querySelectorAll('.step-item').forEach(function(item) {
        const itemStep = parseInt(item.dataset.step);
        item.classList.remove('active', 'completed');

        if (itemStep === step) {
            item.classList.add('active');
        } else if (itemStep < step) {
            item.classList.add('completed');
        }
    });

    // 更新步骤线
    document.querySelectorAll('.step-line').forEach(function(line, index) {
        if (index < step - 1) {
            line.classList.add('active');
        } else {
            line.classList.remove('active');
        }
    });

    // 显示/隐藏对应步骤内容
    for (let i = 1; i <= 6; i++) {
        const stepElement = document.getElementById('step' + i);
        if (stepElement) {
            if (i === step) {
                stepElement.classList.remove('hidden');
            } else {
                stepElement.classList.add('hidden');
            }
        }
    }

    // 滚动到顶部
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

/**
 * 更新确认信息
 */
function updateConfirmInfo() {
    const weiboUrl = document.getElementById('weiboUrl').value.trim();
    const winnerCount = document.getElementById('winnerCount').value;

    // 抽奖类型名称映射
    const typeNames = {
        'like': '❤️ 点赞抽奖',
        'comment': '💬 评论抽奖',
        'repost': '🔁 转发抽奖',
        'mixed': '🎯 混合抽奖'
    };

    document.getElementById('confirmWeiboUrl').textContent = weiboUrl;
    document.getElementById('confirmLotteryType').textContent = typeNames[selectedLotteryType] || selectedLotteryType;
    document.getElementById('confirmWinnerCount').textContent = winnerCount + ' 人';
}

/**
 * 开始抽奖动画
 */
function startLotteryAnimation() {
    const wheel = document.querySelector('.lottery-wheel');
    const progressBar = document.querySelector('.progress-bar');

    if (wheel) {
        wheel.classList.add('spinning');
    }

    if (progressBar) {
        progressBar.style.width = '0%';
        setTimeout(function() {
            progressBar.style.width = '100%';
        }, 100);
    }
}

/**
 * 停止抽奖动画
 */
function stopLotteryAnimation() {
    const wheel = document.querySelector('.lottery-wheel');
    if (wheel) {
        wheel.classList.remove('spinning');
    }
}

/**
 * 页面初始化
 */
document.addEventListener('DOMContentLoaded', function() {
    // 抽奖类型选择
    document.querySelectorAll('.type-card').forEach(function(card) {
        card.addEventListener('click', function() {
            // 移除其他卡片的选中状态
            document.querySelectorAll('.type-card').forEach(function(c) {
                c.classList.remove('selected');
            });

            // 添加选中状态
            this.classList.add('selected');
            selectedLotteryType = this.dataset.type;
        });
    });

    // 回车键快捷操作
    document.getElementById('weiboUrl')?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            goToStep(2);
        }
    });

    document.getElementById('weiboCookie')?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            validateCookie();
        }
    });

    document.getElementById('winnerCount')?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            goToStep(4);
        }
    });

    // 限制中奖人数输入（免费版最多50人）
    document.getElementById('winnerCount')?.addEventListener('input', function() {
        let value = parseInt(this.value);
        if (value < 1) {
            this.value = 1;
        } else if (value > 50) {
            this.value = 50;
            showWarning('免费版最多支持50人，如需更多请升级VIP');
        }
    });

    // Cookie输入提示
    document.getElementById('weiboCookie')?.addEventListener('focus', function() {
        if (!this.value) {
            showInfo('请从浏览器开发者工具中复制完整的Cookie值');
        }
    });

    // Cookie输入验证提示
    document.getElementById('weiboCookie')?.addEventListener('blur', function() {
        const cookie = this.value.trim();
        if (cookie && !cookie.includes('SUB=') && !cookie.includes('SUBP=')) {
            showWarning('Cookie格式可能不正确，请确保包含SUB或SUBP字段');
        }
    });
});

/**
 * 显示提示信息
 */
function showInfo(message) {
    // 可以使用toast或其他提示方式
    console.info(message);
}

/**
 * 显示警告信息
 */
function showWarning(message) {
    // 可以使用toast或其他提示方式
    console.warn(message);
}
