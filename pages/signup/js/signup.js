document.querySelector(".btn-signup").addEventListener("click", function(event) {
    event.preventDefault();

    const email = document.getElementById("email").value;
    const phone = document.getElementById("phone").value;
    const password = document.getElementById("password").value;
    const retypePassword = document.getElementById("retype-password").value;

    if (password !== retypePassword) {
        alert("Sai mật khẩu nhập lại");
        return;
    }

    fetch('signup.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            email: email,
            phone: phone,
            password: password
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            console.log("Signup successful! Email:", email, "Phone:", phone);
            alert("Đăng ký thành công. Vui lòng đăng nhập.");
            window.location.href = "../login/";
        } else {
            alert("Error: " + data.message);
        }
    })
    .catch((error) => {
        console.error('Error:', error);
    });
});

// After signup, users must login to receive an auth token.

function setCookie(cname, cvalue, exdays) {
    const d = new Date();
    d.setTime(d.getTime() + (exdays*24*60*60*1000));
    let expires = "expires="+ d.toUTCString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

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

const signupIcon = document.querySelectorAll(".signup-input-icon");
const passwordInput = document.getElementById("password");
const passwordConfirmInput  = document.getElementById("retype-password");
const passwordIcon = document.getElementById("password-icon");
const retypeIcon = document.getElementById("retype-icon");

signupIcon.forEach((icon) => { 
    icon.addEventListener("click", function(e) {
        if (e.target.id == "password-icon") {
            if (passwordInput.type === "password") {
                passwordInput.type = "text";
                passwordIcon.name = "eye-off-outline";
            } else {
                passwordInput.type = "password";
                passwordIcon.name = "eye-outline";
            }
        }
        else if (e.target.id == "retype-icon") {
            if (passwordConfirmInput.type === "password") {
                passwordConfirmInput.type = "text";
                retypeIcon.name = "eye-off-outline";
            } else {
                passwordConfirmInput.type = "password";
                retypeIcon.name = "eye-outline";
            }
        }
    });
});