// ===========================
// SYMPTOM ANALYZER V2 - UPDATED JS
// ===========================
const API_BASE = 'https://gabayai-server.onrender.com';

let followupGenerated = false;
let analysisData = null;
let showAllConditions = false;

// -------- Validation --------
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
  }
  input.classList.remove('invalid');
  errorElement.style.display = 'none';
  return true;
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
  }
  input.classList.remove('invalid');
  errorElement.style.display = 'none';
  return true;
}

function validateStep1() {
  const ageValid = validateAge(document.getElementById('age'));
  const hValid = validatePositiveNumber(document.getElementById('height'), 'height-error');
  const wValid = validatePositiveNumber(document.getElementById('weight'), 'weight-error');

  if (ageValid && hValid && wValid) {
    nextStep(1);
  } else {
    document
      .querySelector('.invalid')
      ?.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
}

// -------- Step Navigation --------
function nextStep(step) {
  document.getElementById('step' + step).classList.remove('active');
  document.getElementById('step' + (step + 1)).classList.add('active');
}

function prevStep(step) {
  document.getElementById('step' + step).classList.remove('active');
  document.getElementById('step' + (step - 1)).classList.add('active');
}

function toggleRedFlags() {
  const card = document.getElementById('red-flags-card');
  const chevron = document.getElementById('red-flag-chevron');
  card.classList.toggle('collapsed');
  chevron.classList.toggle('fa-chevron-down');
  chevron.classList.toggle('fa-chevron-up');
}

// -------- STEP 2: Logic --------
async function handleStep2() {
  const symptoms = document.getElementById('symptoms').value.trim();
  if (!symptoms) {
    alert('Please describe your symptoms first.');
    return;
  }
  if (!followupGenerated) {
    await generateFollowupQuestions();
  } else {
    await analyzeSymptoms();
  }
}

// Helper: Collect Base Data
function collectBasePayload() {
  return {
    age: document.getElementById('age').value,
    gender: document.getElementById('gender').value,
    height: document.getElementById('height').value,
    weight: document.getElementById('weight').value,
    bloodPressure: document.getElementById('bp').value,
    symptoms: document.getElementById('symptoms').value.trim()
  };
}

// -------- API: Get Follow-up Questions --------
async function generateFollowupQuestions() {
  const analyzing = document.getElementById('analyzing');
  const txt = document.getElementById('analyzing-text');
  txt.textContent = 'Thinking of follow-up questions...';
  analyzing.style.display = 'flex';

  const data = collectBasePayload();
  data.step = 1;

  try {
    const res = await fetch(`${API_BASE}/analyze`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });

    const result = await res.json();
    analyzing.style.display = 'none';

    let questionsArray =
      result && Array.isArray(result.questions) ? result.questions : [];
    if (!questionsArray.length) questionsArray = getFallbackQuestions();

    followupGenerated = true;
    renderFollowupQuestions(questionsArray);

    document.getElementById('step2-main-btn').textContent = 'Analyze Symptoms';
    document.getElementById('followup-section').style.display = 'block';
    document
      .getElementById('followup-section')
      .scrollIntoView({ behavior: 'smooth', block: 'start' });
  } catch (err) {
    analyzing.style.display = 'none';
    console.error('Error generating questions:', err);
    // Auto-skip to analysis if error, to prevent getting stuck
    await analyzeSymptoms();
  }
}

function renderFollowupQuestions(questions) {
  const container = document.getElementById('questions-container');
  container.innerHTML = '';

  questions.forEach((q, idx) => {
    const qText = q.question?.text || q.question || `Question ${idx + 1}`;
    let opts = q.options || [];

    opts = opts.map(o => (typeof o === 'object' ? Object.values(o)[0] : o));

    const group = document.createElement('div');
    group.className = 'form-group';

    const optionsHtml = opts
      .map(opt => `<option value="${opt}">${opt}</option>`)
      .join('');

    group.innerHTML = `
      <label>${qText}</label>
      <select class="followup-input" data-question="${qText}">
        <option value="">Select answer...</option>
        ${optionsHtml}
      </select>
    `;
    container.appendChild(group);
  });
}

function getFallbackQuestions() {
  return [
    {
      question: 'Gaano katagal na?',
      options: ['Ngayon lang', '2-7 araw', 'Mahigit 1 linggo', 'Hindi ko alam']
    },
    {
      question: 'Gaano kalala (1-10)?',
      options: ['Mild (1-3)', 'Moderate (4-6)', 'Severe (7-10)', 'Hindi ko alam']
    },
    {
      question: 'Ano nagpapalala?',
      options: ['Pagkain/Inumin', 'Paggalaw', 'Wala', 'Hindi ko alam']
    }
  ];
}

// -------- API: Final Analysis --------
async function analyzeSymptoms() {
  const analyzing = document.getElementById('analyzing');
  const txt = document.getElementById('analyzing-text');
  txt.textContent = 'Analyzing symptoms (Top 5 Matches)...';
  analyzing.style.display = 'flex';

  const inputs = document.querySelectorAll('.followup-input');
  const answers = Array.from(inputs)
    .map(inp => ({
      question: inp.getAttribute('data-question'),
      answer: inp.value.trim()
    }))
    .filter(a => a.answer);

  const data = collectBasePayload();
  data.step = 2;
  data.answers = answers;

  try {
    const res = await fetch(`${API_BASE}/analyze`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });

    if (!res.ok) throw new Error('Server error');

    analysisData = await res.json();
    analyzing.style.display = 'none';

    nextStep(2);
    showAllConditions = false;
    renderResults();

    trackSymptomAnalyzerUsage(data.symptoms, analysisData);
  } catch (err) {
    analyzing.style.display = 'none';
    console.error('Analysis error:', err);
    alert('Sorry, nagka-error sa server. Try again later.');
  }
}

// -------- Render Results --------
function renderResults() {
  const redCard = document.getElementById('red-flags-card');
  const list = document.getElementById('red-flags-list');

  const triageDiv = document.getElementById('triage-container');
  if (triageDiv && analysisData) {
    const level = analysisData.urgency_level || 'Self Care';
    let color = '#22c55e';
    if (level.includes('Urgent')) color = '#facc15';
    if (level.includes('Emergency')) color = '#ef4444';
    triageDiv.innerHTML = `
      <span class="triage-badge" style="background:${color};">${level}</span>
      <div>${analysisData.triage_message || ''}</div>
    `;
  }

  const drugDiv = document.getElementById('drug-warning-container');
  if (
    drugDiv &&
    analysisData &&
    analysisData.drug_interaction_warning &&
    analysisData.drug_interaction_warning !== 'None'
  ) {
    drugDiv.textContent = `💊 Drug Interaction Warning: ${analysisData.drug_interaction_warning}`;
    drugDiv.style.display = 'block';
  } else if (drugDiv) {
    drugDiv.style.display = 'none';
  }

  if (analysisData.red_flags && analysisData.red_flags.length > 0) {
    list.innerHTML = analysisData.red_flags.map(f => `<li>${f}</li>`).join('');
    redCard.style.display = 'block';
  } else {
    redCard.style.display = 'none';
  }

  const listDiv = document.getElementById('condition-list');
  listDiv.innerHTML = '';

  if (!analysisData.conditions || !analysisData.conditions.length) {
    listDiv.innerHTML = '<p>No specific conditions found.</p>';
    return;
  }

  const allConds = analysisData.conditions;
  const DEFAULT_VISIBLE = 5;
  const visibleCount = showAllConditions
    ? allConds.length
    : Math.min(DEFAULT_VISIBLE, allConds.length);

  allConds.slice(0, visibleCount).forEach((cond, idx) => {
    const level = cond.match_level || 'Possible';
    const color = cond.match_color || '#48bb78';

    let width = '50%';
    if (level === 'High') width = '90%';
    if (level === 'Low') width = '30%';

    const card = document.createElement('div');
    card.className = 'condition-card' + (idx === 0 ? ' selected' : '');
    card.innerHTML = `
      <div class="cond-name">${cond.name}</div>
      <div class="match-bar-bg">
        <div class="match-bar-fill" style="width:${width};background:${color};"></div>
      </div>
      <div class="cond-match-label">${level} Match</div>
    `;
    card.addEventListener('click', () => showConditionDetail(idx));
    listDiv.appendChild(card);
  });

  if (allConds.length > DEFAULT_VISIBLE) {
    const more = document.createElement('div');
    more.className = 'load-more-conditions';
    more.innerHTML = showAllConditions
      ? `<i class="fas fa-chevron-up"></i> Show Top ${DEFAULT_VISIBLE} Only`
      : `<i class="fas fa-chevron-down"></i> Show All ${allConds.length} Results`;

    more.addEventListener('click', () => {
      showAllConditions = !showAllConditions;
      renderResults();
    });
    listDiv.appendChild(more);
  }

  showConditionDetail(0);
}

function showConditionDetail(index) {
  if (!analysisData || !analysisData.conditions[index]) return;
  const cond = analysisData.conditions[index];

  document.querySelectorAll('.condition-card').forEach((c, i) => {
    c.classList.toggle('selected', i === index);
  });

  document.getElementById('detail-placeholder').style.display = 'none';
  document.getElementById('detail-content').style.display = 'block';

  document.getElementById('det-name').textContent = cond.name;

  const badge = document.getElementById('det-badge');
  badge.textContent = `${cond.match_level} Match`;
  badge.style.backgroundColor = cond.match_color;

  document.getElementById('det-overview').textContent = cond.overview;

  const symList = cond.symptoms_matched || [];
  document.getElementById('det-symptoms').innerHTML = symList.length
    ? symList.map(s => `<li>${s}</li>`).join('')
    : '<li>No matches listed</li>';

  const treatList = cond.treatment || [];
  document.getElementById('det-treatment').innerHTML = treatList.length
    ? treatList.map(t => `<li>${t}</li>`).join('')
    : '<li>See doctor for advice.</li>';
}

function trackSymptomAnalyzerUsage(symptoms, diagnosis) {
  fetch('track_symptom_usage.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      symptoms,
      diagnosis,
      timestamp: new Date().toISOString()
    })
  }).catch(e => console.warn('Track usage failed (non-critical)'));
}

// Optional: payload with language
function collectBasePayloadWithLanguage() {
  const selectedLang = window.CURRENT_LANGUAGE || 'en';

  return {
    age: document.getElementById('age').value,
    gender: document.getElementById('gender').value,
    height: document.getElementById('height').value,
    weight: document.getElementById('weight').value,
    bloodPressure: document.getElementById('bp').value,
    symptoms: document.getElementById('symptoms').value.trim(),
    language: selectedLang
  };
} 

