function getCookie(cname) {
    let name = cname + "=";
    let decodedCookie = decodeURIComponent(document.cookie);
    let ca = decodedCookie.split(';');
    for(let i = 0; i <ca.length; i++) {
      let c = ca[i];
      while (c.charAt(0) == ' ') {
        c = c.substring(1);
      }
      if (c.indexOf(name) == 0) {
        return c.substring(name.length, c.length);
      }
    }
    return "";
  }
  
  
  const headerAccountBtnGroup = document.querySelector(".top-content__account-btn-group");
  const headerLoginBtn = headerAccountBtnGroup.querySelector(".account-btn-group__login-btn");
  const headerSignUpBtn = headerAccountBtnGroup.querySelector(".account-btn-group__sign-up-btn");
  const headerAccountBtn = headerAccountBtnGroup.querySelector(".account-btn-group__account-btn");
  
  window.addEventListener("load",function(event){
    fetch("../../server/data-controller/check-user-info.php?action=check-user-info")
      .then(r => r.text())
      .then(t => {
        const loggedIn = t !== 'no-data';
        if (loggedIn) {
          headerLoginBtn.classList.add("hide");
          headerSignUpBtn.classList.add("hide");
          headerAccountBtn.classList.remove("hide");
        } else {
          headerLoginBtn.classList.remove("hide");
          headerSignUpBtn.classList.remove("hide");
          headerAccountBtn.classList.add("hide");
        }
      })
      .catch(() => {
        headerLoginBtn.classList.remove("hide");
        headerSignUpBtn.classList.remove("hide");
        headerAccountBtn.classList.add("hide");
      });
  });