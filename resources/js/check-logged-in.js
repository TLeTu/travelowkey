window.addEventListener('pageshow', () => {
    // Verify auth via server (JWT cookie), redirect if not logged in
    const url = '../../server/data-controller/check-user-info.php?action=check-user-info';
    fetch(url, { credentials: 'same-origin' })
      .then(r => r.text())
      .then(t => {
        if (t === 'no-data') {
          window.location.href = '../login/';
        }
      })
      .catch(() => {
        window.location.href = '../login/';
      });
});