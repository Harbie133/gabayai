// Load doctors from PHP
async function loadDoctors() {
    try {
        const response = await fetch("getDoctors.php");
        const doctors = await response.json();
        const list = document.getElementById("doctorList");

        list.innerHTML = "";

        doctors.forEach(doc => {
            // Use doctor image from DB or fallback
            const imgPath = doc.image;

            const card = document.createElement("div");
            card.className = "card";

            card.innerHTML = `
                <img src="${imgPath}" alt="${doc.name}">
                <h3>${doc.name}</h3>
                <p><strong>Specialty:</strong> ${doc.specialty}</p>
                <p class="${doc.availability === "Available" ? "available" : "not-available"}">
                    ${doc.availability}
                </p>
            `;

            list.appendChild(card);
        });
    } catch (error) {
        console.error("Error loading doctors:", error);
    }
}

// Call on page load
loadDoctors();
