const API_URL = "http://localhost:8000";

// ---------------- LOGIN ----------------
document.addEventListener("DOMContentLoaded", () => {
  const loginForm = document.getElementById("loginForm");
  if (loginForm) {
    loginForm.addEventListener("submit", async (e) => {
      e.preventDefault();
      const email = document.getElementById("email").value;
      const password = document.getElementById("password").value;

      const res = await fetch(`${API_URL}/usuarios/login`, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email, password })
      });

      const data = await res.json();
      if (data.token) {
        localStorage.setItem("token", data.token);
        Swal.fire("✅ Bienvenido", "Inicio de sesión exitoso", "success")
          .then(() => window.location.href = "dashboard.html");
      } else {
        Swal.fire("❌ Error", "Credenciales incorrectas", "error");
      }
    });
  }

  if (window.location.pathname.includes("dashboard.html")) {
    cargarMarcajes();
  }
});

// ---------------- MARCAR ENTRADA/SALIDA ----------------
async function marcar(tipo) {
  const token = localStorage.getItem("token");
  if (!token) return window.location.href = "login.html";

  // obtener geolocalización
  navigator.geolocation.getCurrentPosition(async (pos) => {
    const lat = pos.coords.latitude;
    const lng = pos.coords.longitude;

    const res = await fetch(`${API_URL}/marcajes`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "Authorization": `Bearer ${token}`
      },
      body: JSON.stringify({ usuario_id: 1, tipo, lat, lng })
    });

    const data = await res.json();
    if (data.success) {
      Swal.fire("✅ Listo", `Marcaje de ${tipo} registrado`, "success");
      cargarMarcajes();
    }
  });
}

// ---------------- CARGAR HISTORIAL ----------------
async function cargarMarcajes() {
  const token = localStorage.getItem("token");
  const res = await fetch(`${API_URL}/marcajes`, {
    headers: { "Authorization": `Bearer ${token}` }
  });
  const data = await res.json();

  const tbody = document.getElementById("tablaMarcajes");
  tbody.innerHTML = data.map(m => `
    <tr>
      <td>${m.usuario_id}</td>
      <td>${m.tipo}</td>
      <td>${m.marcado_en}</td>
      <td>${m.lat}, ${m.lng}</td>
    </tr>
  `).join("");
}

// ---------------- LOGOUT ----------------
function logout() {
  localStorage.removeItem("token");
  window.location.href = "login.html";
}
