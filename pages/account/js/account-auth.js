window.addEventListener("load", CheckAuthUser);

const warningAuth = document.getElementById("warning-auth");

async function CheckAuthUser() {
  try {
    const r = await fetch('../../server/data-controller/check-user-info.php?action=check-user-info', { credentials: 'include' });
    const t = await r.text();
    if (!r.ok || t === 'no-data') return;
    const info = JSON.parse(t)[0];
    let complete = true;
    for (const k in info) { if (info[k] == null) { complete = false; break; } }
    if (!complete) {
      document.getElementById("user-info-view").classList.add("hide");
      document.getElementById("user-info-form").classList.remove("hide");
    }
  } catch {}
}

function getCookie(cname) {
  let name = cname + "=";
  let decodedCookie = decodeURIComponent(document.cookie);
  let ca = decodedCookie.split(";");
  for (let i = 0; i < ca.length; i++) {
    let c = ca[i];
    while (c.charAt(0) == " ") {
      c = c.substring(1);
    }
    if (c.indexOf(name) == 0) {
      return c.substring(name.length, c.length);
    }
  }
  return "";
}
