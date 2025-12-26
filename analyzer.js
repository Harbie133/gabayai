// ===========================
// SYMPTOM ANALYZER - CLEAN JS
// (No Navbar/Language Functions)
// ===========================

// Validation Functions
function validateAge(input) {
  const errorElement = document.getElementById('age-error');
  const value = parseInt(input.value);
  
  if (input.value === '') {
    input.classList.remove('invalid');
    errorElement.style.display = 'none';
    return true;
  }
  
  if (isNaN(value) || value < 1 || value > 130) {
    input.classList.add('invalid');
    errorElement.style.display = 'block';
    return false;
  } else {
    input.classList.remove('invalid');
    errorElement.style.display = 'none';
    return true;
  }
}

function validatePositiveNumber(input, errorId) {
  const errorElement = document.getElementById(errorId);
  const value = parseFloat(input.value);
  
  if (input.value === '') {
    input.classList.remove('invalid');
    errorElement.style.display = 'none';
    return true;
  }
  
  if (isNaN(value) || value < 1) {
    input.classList.add('invalid');
    errorElement.style.display = 'block';
    return false;
  } else {
    input.classList.remove('invalid');
    errorElement.style.display = 'none';
    return true;
  }
}

function validateStep1() {
  const ageValid = validateAge(document.getElementById('age'));
  const heightValid = validatePositiveNumber(document.getElementById('height'), 'height-error');
  const weightValid = validatePositiveNumber(document.getElementById('weight'), 'weight-error');
  
  if (ageValid && heightValid && weightValid) {
    nextStep(1);
  } else {
    // Scroll to the first error
    const firstError = document.querySelector('.invalid');
    if (firstError) {
      firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  }
}

// Step Navigation Functions
function nextStep(step) {
  document.getElementById("step" + step).classList.remove("active");
  document.getElementById("step" + (step + 1)).classList.add("active");
}

function prevStep(step) {
  document.getElementById("step" + step).classList.remove("active");
  document.getElementById("step" + (step - 1)).classList.add("active");
}

// Symptom Analysis Function
async function analyzeSymptoms() {
  document.getElementById("analyzing").style.display = "flex";

  const data = {
    age: document.getElementById("age").value,
    gender: document.getElementById("gender").value,
    height: document.getElementById("height").value,
    weight: document.getElementById("weight").value,
    bloodPressure: document.getElementById("bp").value,
    symptoms: document.getElementById("symptoms").value
  };

  try {
    const res = await fetch("http://localhost:5000/analyze", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(data)
    });

    const result = await res.json();
    document.getElementById("analyzing").style.display = "none";
    document.getElementById("step2").classList.remove("active");
    document.getElementById("step3").classList.add("active");

    let html = "";

    if (result.conditions && Array.isArray(result.conditions)) {
      result.conditions.forEach(cond => {
        const steps = cond.remedy.split(/\r?\n/).filter(s => s.trim() !== "");
        html += `
          <div class="card">
            <h3>${cond.name}</h3>
            <ul>
              ${steps.map(step => `<li>${step.replace(/^- /, "")}</li>`).join("")}
            </ul>
          </div>
        `;
      });
    }

    if (result.red_flags && result.red_flags.length > 0) {
      html += `
        <div class="card red-flags">
          <h3>Important Health Notices</h3>
          <ul>
            ${result.red_flags.map(flag => `<li>${flag}</li>`).join("")}
          </ul>
        </div>
      `;
    }

    document.getElementById("results").innerHTML = html;
    
    // Track usage after successful analysis
    trackSymptomAnalyzerUsage(data.symptoms, result);
    
  } catch (err) {
    document.getElementById("analyzing").style.display = "none";
    alert("Error analyzing symptoms. Please try again.");
    console.error("❌ Frontend error:", err);
  }
}

// Usage Tracking Function
function trackSymptomAnalyzerUsage(symptoms, diagnosis) {
  fetch('track_symptom_usage.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      symptoms: symptoms,
      diagnosis: diagnosis,
      timestamp: new Date().toISOString()
    })
  })
  .then(response => response.json())
  .then(data => {
    console.log('Symptom analyzer usage tracked:', data);
  })
  .catch(error => {
    console.error('Error tracking symptom usage:', error);
  });
}
