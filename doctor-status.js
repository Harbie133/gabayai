// doctor-status.js

async function doctorHeartbeat(){
  try {
    await fetch("doctor_heartbeat.php", {
      method: "POST",
      cache: "no-store",
      credentials: "include"
    });
  } catch(e) {
    console.error("heartbeat error", e);
  }
}

document.addEventListener('DOMContentLoaded', () => {
  doctorHeartbeat();
  setInterval(doctorHeartbeat, 7000);
});
