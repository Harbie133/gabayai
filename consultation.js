// Toggle microphone
const toggleMic = document.getElementById('toggleMic');
toggleMic.addEventListener('click', () => {
  toggleMic.textContent = toggleMic.textContent.includes('Mute') ? '🎤 Unmute' : '🎤 Mute';
});

// Toggle camera
const toggleCam = document.getElementById('toggleCam');
toggleCam.addEventListener('click', () => {
  toggleCam.textContent = toggleCam.textContent.includes('Off') ? '📷 Video On' : '📷 Video Off';
});

// Chat functionality
const chatInput = document.getElementById('chatInput');
const sendMessage = document.getElementById('sendMessage');
const chatMessages = document.getElementById('chatMessages');

sendMessage.addEventListener('click', () => {
  if(chatInput.value.trim() !== '') {
    const msg = document.createElement('p');
    msg.textContent = 'You: ' + chatInput.value;
    chatMessages.appendChild(msg);
    chatInput.value = '';
    chatMessages.scrollTop = chatMessages.scrollHeight;
  }
});
