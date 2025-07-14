<style>
/* ========= 页脚样式 ========= */
footer {
  background-color: #f8f9fa; /* 浅灰色背景 */
  color: #333333; /* 深灰色文字 */
  border-top: 1px solid #e9ecef; /* 浅灰色边框 */
  padding: 1.5rem 0; /* 内边距 */
  position: fixed; /* 使用 sticky */
  bottom: 0; /* 确保页脚在滚动到最底部时固定 */
  width: 100%; /* 满屏宽度 */
  z-index: 100;
  box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.05); /* 轻微阴影 */
  height: 60px; /* 固定高度 */
  display: flex;
  align-items: center; /* 垂直居中 */
  justify-content: center; /* 水平居中 */
  transition: var(--transition);
}

.footer-content {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 1rem;
  text-align: center;
}

.footer p {
  margin: 0.5rem 0;
  font-size: 0.9rem;
  line-height: 1.5;
  font-family: "Roboto", sans-serif; /* 商务风格字体 */
}

.footer a {
  color: #007bff !important; /* 蓝色链接，符合商务风格 */
  transition: opacity 0.2s ease;
}

.footer a:hover {
  opacity: 0.8;
  text-decoration: underline;
}

/* 移动端优化 */
@media (max-width: 768px) {
  footer {
    height: auto; /* 移动端取消固定高度 */
    padding: 1rem 0;
    position: static; /* 移动端不固定 */
  }

  .footer p {
    font-size: 0.8rem;
    padding: 0 1rem;
  }
}


</style>

<footer class="footer">
        <div class="footer-content">
            <p style="color: var(--current-accent)">
                &copy; 2025 ZICMS · 版权所有 
                <a href="https://beian.miit.gov.cn" target="_blank">
                    <?php echo get_beian_number(); ?>
                </a>
            </p>
            <?php if($currentConfig['stat_code']) echo $currentConfig['stat_code']; ?>
        </div>
    </footer>
