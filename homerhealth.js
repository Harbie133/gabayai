
async function loadHealthTipsFromAI() {
  const titleEl = document.getElementById("tipTitle");
  const loadingEl = document.getElementById("tipLoading");
  const errorEl = document.getElementById("tipError");
  const columnsEl = document.getElementById("tipColumns");
  const tagalogEl = document.getElementById("tagalogContent");
  const englishEl = document.getElementById("englishContent");
  const disclaimerEl = document.getElementById("tipDisclaimer");

  if (!titleEl) return;

  loadingEl.style.display = "block";
  errorEl.style.display = "none";
  columnsEl.style.display = "none";
  tagalogEl.innerHTML = "";
  englishEl.innerHTML = "";
  disclaimerEl.textContent = "";

  try {
    const resp = await fetch("http://localhost:5100/api/health-tips", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        topic: "fever-home-care",
        language: "both"
      }),
    });

    if (!resp.ok) {
      throw new Error("Server error " + resp.status);
    }

    const data = await resp.json();

    titleEl.textContent = data.title || "Health Tip";
    disclaimerEl.textContent = data.disclaimer || "";

    if (Array.isArray(data.tagalog_paragraphs) && data.tagalog_paragraphs.length) {
      tagalogEl.innerHTML = data.tagalog_paragraphs
        .map(p => `<p>${p}</p>`)
        .join("");
      document.getElementById("tipTagalog").style.display = "block";
    } else {
      document.getElementById("tipTagalog").style.display = "none";
    }

    if (Array.isArray(data.english_paragraphs) && data.english_paragraphs.length) {
      englishEl.innerHTML = data.english_paragraphs
        .map(p => `<p>${p}</p>`)
        .join("");
      document.getElementById("tipEnglish").style.display = "block";
    } else {
      document.getElementById("tipEnglish").style.display = "none";
    }

    columnsEl.style.display = "flex";
    loadingEl.style.display = "none";
  } catch (err) {
    console.error("Health tips load error:", err);
    loadingEl.style.display = "none";
    errorEl.style.display = "block";
    errorEl.textContent = "Hindi ma-load ang health tips ngayon. Pakisubukang muli mamaya.";
  }
}

// sa DOMContentLoaded, idagdag mo tawag:
window.addEventListener('DOMContentLoaded', function() {
  syncPatientData();
  loadHealthTipsFromAI();
});