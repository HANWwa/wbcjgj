/**
 * 抽奖JavaScript
 * @神奇奶酪
 */

let currentStep = 1;
let selectedLotteryType = '';
let weiboData = null;

/**
 * 解析微博
 */
function parseWeibo() {
    const weiboUrl = document.getElementById('weiboUrl').value.trim();

    if (!weiboUrl) {
        showError('请输入微博链接');
        return;
    }

    // 简单验证链接格式
    if (!weiboUrl.includes('weibo.com') && !weiboUrl.includes('weibo.cn') && !weiboUrl.includes('t.cn')) {
        showError('请输入有效的微博链接');
        return;
    }

    ajax('ajax_lottery.php', {
        action: 'parse_weibo',
        weibo_url: weiboUrl
    }, function(result) {
        if (result.success) {
            weiboData = result.data;
            showWeiboInfo(result.data);
            goToStep(2);
            showSuccess('微博解析成功！');
        } else {
            showError(result.message);
        }
    });
}

/**
 * 显示微博信息
 */
function showWeiboInfo(data) {
    document.getElementById('likeCount').textContent = formatNumber(data.like_count);
    document.getElementById('commentCount').textContent = formatNumber(data.comment_count);
    document.getElementById('repostCount').textContent = formatNumber(data.repost_count);
}

/**
 * 开始抽奖
 */
function startLottery() {
    if (!selectedLotteryType) {
        showError('请选择抽奖类型');
        return;
    }

    const weiboUrl = document.getElementById('weiboUrl').value.trim();
    const winnerCount = parseInt(document.getElementById('winnerCount').value);

    if (!winnerCount || winnerCount < 1) {
        showError('请输入有效的中奖人数');
        return;
    }

    // 显示抽奖动画
    goToStep(3);

    // 开始抽奖动画
    startLotteryAnimation();

    // 发送抽奖请求
    ajax('ajax_lottery.php', {
        action: 'start_lottery',
        weibo_url: weiboUrl,
        lottery_type: selectedLotteryType,
        winner_count: winnerCount
    }, function(result) {
        if (result.success) {
            // 延迟显示结果，让动画播放完整
            setTimeout(function() {
                showLotteryResult(result.data);
                goToStep(4);
            }, 3000);
        } else {
            stopLotteryAnimation();
            showError(result.message);
            goToStep(2);
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
    weiboData = null;

    document.getElementById('weiboUrl').value = '';
    document.getElementById('winnerCount').value = '1';

    // 移除所有类型卡片的选中状态
    document.querySelectorAll('.type-card').forEach(function(card) {
        card.classList.remove('selected');
    });

    goToStep(1);
}

/**
 * 返回步骤1
 */
function backToStep1() {
    goToStep(1);
}

/**
 * 切换到指定步骤
 */
function goToStep(step) {
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
    for (let i = 1; i <= 4; i++) {
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
 * 格式化数字
 */
function formatNumber(num) {
    if (num >= 10000) {
        return (num / 10000).toFixed(1) + 'w';
    }
    return num;
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
            parseWeibo();
        }
    });

    document.getElementById('winnerCount')?.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            startLottery();
        }
    });

    // 限制中奖人数输入
    document.getElementById('winnerCount')?.addEventListener('input', function() {
        let value = parseInt(this.value);
        if (value < 1) {
            this.value = 1;
        } else if (value > 100) {
            this.value = 100;
        }
    });
});
