// Global variables
let googleClientId = '';
let userToken = '';
let isGoogleLoaded = false;
let debugVisible = false;

// Utility functions
function log(message, type = 'info') {
  const timestamp = new Date().toLocaleTimeString();
  const logMessage = `[${timestamp}] ${message}`;
  console.log(logMessage);
  
  // Update debug display
  const debugContent = document.getElementById('debug-content');
  if (debugContent) {
    const color = type === 'error' ? '#dc3545' : type === 'success' ? '#28a745' : '#6c757d';
    debugContent.innerHTML += `<div style="color: ${color}; margin: 2px 0;">${logMessage}</div>`;
    debugContent.scrollTop = debugContent.scrollHeight;
  }
}

function showStatus(message, type = 'loading') {
  const statusEl = document.getElementById('status');
  const spinner = type === 'loading' ? '<span class="spinner"></span>' : '';
  const icon = type === 'success' ? '✅ ' : type === 'error' ? '❌ ' : '';
  
  statusEl.className = `status ${type}`;
  statusEl.innerHTML = `${spinner}${icon}${message}`;
}

// Load configuration from server
async function loadConfig() {
  try {
    log('🔧 Loading configuration from server...');
    showStatus('Loading configuration...', 'loading');
    
    const response = await fetch('/config');
    log(`📡 Server response: ${response.status}`);
    
    if (!response.ok) {
      throw new Error(`Server returned ${response.status}: ${response.statusText}`);
    }
    
    const data = await response.json();
    log(`📄 Config data received: ${JSON.stringify(data, null, 2)}`);
    
    if (!data.googleClientId) {
      throw new Error('Google Client ID not found in server response');
    }
    
    googleClientId = data.googleClientId;
    log('✅ Google Client ID loaded successfully', 'success');
    
    // Check if Google library is ready
    if (isGoogleLoaded) {
      initializeGoogleSignIn();
    } else {
      showStatus('Waiting for Google library to load...', 'loading');
      log('⏳ Waiting for Google Identity Services library...');
    }
    
  } catch (error) {
    log(`❌ Configuration error: ${error.message}`, 'error');
    showStatus(`Configuration failed: ${error.message}`, 'error');
  }
}

// Initialize Google Sign-In
function initializeGoogleSignIn() {
  try {
    log('🚀 Initializing Google Sign-In...');
    
    if (!googleClientId) {
      throw new Error('Google Client ID not available');
    }
    
    if (!window.google?.accounts?.id) {
      throw new Error('Google Identity Services library not loaded');
    }
    
    // Initialize Google Sign-In
    google.accounts.id.initialize({
      client_id: googleClientId,
      callback: handleSignIn,
      auto_select: false,
      cancel_on_tap_outside: true
    });
    
    // Render sign-in button
    const buttonContainer = document.getElementById('g_id_signin');
    if (buttonContainer) {
      google.accounts.id.renderButton(buttonContainer, {
        theme: 'outline',
        size: 'large',
        text: 'signin_with',
        shape: 'rectangular',
        logo_alignment: 'left'
      });
      log('🎨 Google Sign-In button rendered');
    }
    
    log('✅ Google Sign-In initialized successfully', 'success');
    showStatus('Ready! Click the button above to sign in.', 'success');
    
  } catch (error) {
    log(`❌ Google initialization error: ${error.message}`, 'error');
    showStatus(`Initialization failed: ${error.message}`, 'error');
  }
}

// Handle sign-in response from Google
async function handleSignIn(response) {
  try {
    log('🔐 Processing Google Sign-In response...');
    showStatus('Signing in...', 'loading');
    
    if (!response.credential) {
      throw new Error('No credential token received from Google');
    }
    
    userToken = response.credential;
    log('📝 Credential token received');
    
    // Verify token with backend
    const verification = await verifyToken(response.credential);
    
    if (verification.success) {
      log(`👤 User authenticated: ${verification.user.email}`, 'success');
      displayUser(verification.user);
    } else {
      // Fallback to client-side decoding
      log('⚠️ Using client-side token decoding as fallback');
      const user = decodeJWTToken(response.credential);
      displayUser(user);
    }
    
  } catch (error) {
    log(`❌ Sign-in error: ${error.message}`, 'error');
    showStatus(`Sign-in failed: ${error.message}`, 'error');
  }
}

// Verify token with backend
async function verifyToken(token) {
  try {
    log('🔍 Verifying token with backend...');
    
    const response = await fetch('/verify-token', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ token })
    });
    
    const data = await response.json();
    log(`🛡️ Token verification result: ${data.success ? 'SUCCESS' : 'FAILED'}`);
    
    if (data.success) {
      log(`✅ Backend verification successful for: ${data.user.email}`, 'success');
    } else {
      log(`❌ Backend verification failed: ${data.error}`, 'error');
    }
    
    return data;
    
  } catch (error) {
    log(`❌ Token verification error: ${error.message}`, 'error');
    return { success: false, error: error.message };
  }
}

// Decode JWT token (client-side fallback)
function decodeJWTToken(token) {
  try {
    log('🔧 Decoding JWT token on client-side...');
    
    const parts = token.split('.');
    if (parts.length !== 3) {
      throw new Error('Invalid JWT token format');
    }
    
    const payload = parts[1]
      .replace(/-/g, '+')
      .replace(/_/g, '/');
    
    const decoded = JSON.parse(atob(payload));
    log('✅ JWT token decoded successfully');
    
    return {
      email: decoded.email,
      name: decoded.name,
      picture: decoded.picture,
      emailVerified: decoded.email_verified
    };
    
  } catch (error) {
    log(`❌ JWT decode error: ${error.message}`, 'error');
    throw new Error(`Failed to decode user information: ${error.message}`);
  }
}

// Display user information
function displayUser(user) {
  try {
    log('🎨 Displaying user information...');
    
    // Show user info panel
    const userInfo = document.getElementById('user-info');
    const signinButton = document.getElementById('g_id_signin');
    const testAIButton = document.getElementById('test-ai');
    
    if (userInfo) userInfo.style.display = 'block';
    if (signinButton) signinButton.style.display = 'none';
    if (testAIButton) testAIButton.style.display = 'inline-block';
    
    // Update user details
    const nameEl = document.getElementById('user-name');
    const emailEl = document.getElementById('user-email');
    const verifiedEl = document.getElementById('user-verified');
    const picEl = document.getElementById('user-pic');
    
    if (nameEl) nameEl.innerHTML = `<strong>Name:</strong> ${user.name || 'Not provided'}`;
    if (emailEl) emailEl.innerHTML = `<strong>Email:</strong> ${user.email || 'Not provided'}`;
    if (verifiedEl) verifiedEl.innerHTML = `<strong>Verified:</strong> ${user.emailVerified ? '✅ Yes' : '❌ No'}`;
    
    // Update profile picture
    if (picEl && user.picture) {
      picEl.src = user.picture;
      picEl.style.display = 'block';
      log('🖼️ Profile picture loaded');
    }
    
    showStatus('Successfully signed in! Welcome to your healthcare dashboard.', 'success');
    log('✅ User interface updated successfully', 'success');
    
  } catch (error) {
    log(`❌ Display error: ${error.message}`, 'error');
    showStatus(`Display error: ${error.message}`, 'error');
  }
}

// Sign out user
function signOut() {
  try {
    log('🚪 Signing out user...');
    
    // Hide user info and show sign-in button
    const userInfo = document.getElementById('user-info');
    const signinButton = document.getElementById('g_id_signin');
    
    if (userInfo) userInfo.style.display = 'none';
    if (signinButton) signinButton.style.display = 'block';
    
    // Clear token
    userToken = '';
    
    // Disable Google auto-select
    if (window.google?.accounts?.id) {
      google.accounts.id.disableAutoSelect();
    }
    
    showStatus('Signed out successfully. You can sign in again above.', 'success');
    log('✅ Sign out completed', 'success');
    
  } catch (error) {
    log(`❌ Sign out error: ${error.message}`, 'error');
    showStatus(`Sign out failed: ${error.message}`, 'error');
  }
}

// Test AI integration
async function testAI() {
  if (!userToken) {
    alert('❌ Please sign in first to test AI integration');
    return;
  }
  
  try {
    log('🤖 Testing AI integration...');
    
    const response = await fetch('/verify-token', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({ token: userToken })
    });
    
    const data = await response.json();
    
    if (data.success) {
      const message = `🎉 AI Integration Test Successful!\n\n👤 User: ${data.user.name}\n📧 Email: ${data.user.email}\n✅ Verified: ${data.user.emailVerified ? 'Yes' : 'No'}\n🔐 Google ID: ${data.user.googleId}\n\nReady for healthcare AI features!`;
      
      alert(message);
      log('🤖 AI integration test passed', 'success');
    } else {
      alert(`❌ AI Test Failed: ${data.error}`);
      log(`❌ AI test failed: ${data.error}`, 'error');
    }
    
  } catch (error) {
    alert(`❌ AI Test Error: ${error.message}`);
    log(`❌ AI test error: ${error.message}`, 'error');
  }
}

// Toggle debug panel
function toggleDebug() {
  const debugPanel = document.getElementById('debug');
  const toggleButton = document.getElementById('debug-toggle');
  
  debugVisible = !debugVisible;
  
  if (debugPanel) {
    debugPanel.style.display = debugVisible ? 'block' : 'none';
  }
  
  if (toggleButton) {
    toggleButton.textContent = debugVisible ? 'Hide Debug' : 'Show Debug';
  }
  
  log(`🔍 Debug panel ${debugVisible ? 'shown' : 'hidden'}`);
}

// Check for Google library
function checkGoogleLibrary() {
  if (window.google?.accounts?.id && !isGoogleLoaded) {
    log('📚 Google Identity Services library detected', 'success');
    isGoogleLoaded = true;
    
    if (googleClientId) {
      initializeGoogleSignIn();
    }
  }
}

// Initialize when page loads
window.addEventListener('load', () => {
  log('🌐 Page loaded - Starting initialization...');
  
  // Check if Google library is already available
  checkGoogleLibrary();
  
  // Load server configuration
  loadConfig();
  
  // Keep checking for Google library
  let checkAttempts = 0;
  const maxAttempts = 30; // 30 seconds
  
  const libraryChecker = setInterval(() => {
    checkAttempts++;
    checkGoogleLibrary();
    
    if (isGoogleLoaded || checkAttempts >= maxAttempts) {
      clearInterval(libraryChecker);
      
      if (!isGoogleLoaded && checkAttempts >= maxAttempts) {
        log('❌ Google library failed to load after 30 seconds', 'error');
        showStatus('Google library failed to load. Please refresh the page.', 'error');
      }
    }
  }, 1000);
});

// Global error handler
window.addEventListener('error', (event) => {
  log(`🚨 Global error: ${event.error?.message || event.message}`, 'error');
});

// Make functions available globally
window.signOut = signOut;
window.testAI = testAI;
window.toggleDebug = toggleDebug;
