/* assets/js/main.js — 공통 JS */

'use strict';

// ── 툴팁 초기화 (Bootstrap) ─────────────────────────────────
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
    new bootstrap.Tooltip(el);
  });

  // 플래시 메시지 자동 닫기 (4초)
  const alerts = document.querySelectorAll('.alert.alert-dismissible');
  alerts.forEach(alert => {
    setTimeout(() => {
      const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
      if (bsAlert) bsAlert.close();
    }, 4000);
  });

  // 파일 업로드: 선택된 파일 목록 표시
  const fileInput = document.querySelector('input[type="file"][multiple]');
  if (fileInput) {
    fileInput.addEventListener('change', function () {
      const label = this.nextElementSibling;
      if (label && label.classList.contains('file-label')) {
        label.textContent = this.files.length > 0
          ? Array.from(this.files).map(f => f.name).join(', ')
          : '파일을 선택하세요';
      }
    });
  }

  // 뒤로가기 버튼 확인 (글쓰기 폼에서 이탈 시)
  const writeForm = document.querySelector('form[enctype="multipart/form-data"]');
  if (writeForm) {
    let formChanged = false;
    writeForm.querySelectorAll('input, textarea').forEach(el => {
      el.addEventListener('input', () => { formChanged = true; });
    });
    writeForm.addEventListener('submit', () => { formChanged = false; });
    window.addEventListener('beforeunload', e => {
      if (formChanged) {
        e.preventDefault();
        e.returnValue = '';
      }
    });
  }
});

// ── 이미지 라이트박스 (게시글 본문 이미지 클릭 시 확대) ─────
document.addEventListener('click', function (e) {
  if (e.target.tagName === 'IMG' && e.target.closest('.post-content')) {
    const overlay = document.createElement('div');
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.85);z-index:9999;display:flex;align-items:center;justify-content:center;cursor:zoom-out';
    const img = document.createElement('img');
    img.src = e.target.src;
    img.style.cssText = 'max-width:90vw;max-height:90vh;border-radius:4px;box-shadow:0 0 40px rgba(0,0,0,.5)';
    overlay.appendChild(img);
    overlay.addEventListener('click', () => overlay.remove());
    document.body.appendChild(overlay);
  }
});
