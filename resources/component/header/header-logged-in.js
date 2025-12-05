const headerDefault = document.getElementById("header-default");
const headerLoggedIn = document.getElementById("header-logged-in");

window.addEventListener("load", () => {
  fetch("../../server/data-controller/check-user-info.php?action=check-user-info")
    .then(r => r.text())
    .then(t => {
    const loggedIn = t !== 'no-data';
    if (loggedIn) {
      headerDefault.classList.add('hide');
      headerLoggedIn.classList.remove('hide');
    } else {
      headerDefault.classList.remove('hide');
      headerLoggedIn.classList.add('hide');
    }
    })
    .catch(() => {
    headerDefault.classList.remove('hide');
    headerLoggedIn.classList.add('hide');
    });
});

// no userId cookie needed