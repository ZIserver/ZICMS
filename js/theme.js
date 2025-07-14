// theme.js
document.addEventListener('DOMContentLoaded', () => {
  // 初始化主题
  const savedTheme = localStorage.getItem('theme') || 'light';
  document.documentElement.setAttribute('data-theme', savedTheme);
  
  // 绑定所有切换器
  document.querySelectorAll('.theme-toggle').forEach(toggle => {
    toggle.checked = savedTheme === 'dark';
    
    toggle.addEventListener('change', (e) => {
      const theme = e.target.checked ? 'dark' : 'light';
      document.documentElement.setAttribute('data-theme', theme);
      localStorage.setItem('theme', theme);
    });
  });
});
