document.querySelector(".btn-login").addEventListener("click", async (e) => {
  e.preventDefault();

  let emailOrPhone = document.querySelector('input[type="text"]').value;
  let password = document.querySelector('input[type="password"]').value;

  try {
    let response = await fetch("login.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ emailOrPhone, password }),
    });

    let result = await response.json();

    if (result.success) {
      // JWT is set via HttpOnly cookie by server
      const status = await getProfileStatus();
      if (!status.loggedIn) { window.location.href = "../login/"; return; }
      if (status.profileComplete) {
        window.location.href = "../main/";
      } else {
        window.location.href = "../account/";
      }
    } else {
      alert("Invalid email/phone or password");
    }
  } catch (error) {
    console.error("Error:", error);
  }
});

async function getProfileStatus() {
  try {
    const r = await fetch("../../server/data-controller/check-user-info.php?action=check-user-info", { credentials: 'include' });
    const t = await r.text();
    if (!r.ok || t === 'no-data') return { loggedIn: false, profileComplete: false };
    const info = JSON.parse(t)[0];
    let complete = true;
    for (const k in info) { if (info[k] == null) { complete = false; break; } }
    return { loggedIn: true, profileComplete: complete };
  } catch { return { loggedIn: false, profileComplete: false }; }
}

const loginIcon = document.querySelector(".login-input-icon");
const passwordInput = document.getElementById("txt-password");

loginIcon.addEventListener("click", () => { 
  if (passwordInput.type === "password") {
    passwordInput.type = "text";
    loginIcon.name = "eye-off-outline";
  } else {
    passwordInput.type = "password";
    loginIcon.name = "eye-outline";
  }
});