// auth-guard.js - Reusable authentication protection
class AuthGuard {
    constructor(options = {}) {
        this.loginUrl = options.loginUrl || 'userlogin.html';
        this.landingUrl = options.landingUrl || 'landingpage.html';
        this.checkSessionUrl = options.checkSessionUrl || 'check_session.php';
        this.logoutUrl = options.logoutUrl || 'logout.php';
        this.showWelcomeMessage = options.showWelcomeMessage !== false;
    }

    // Check if user is logged in
    async checkAuthentication() {
        try {
            const response = await fetch(this.checkSessionUrl);
            const data = await response.json();
            
            if (!data.logged_in) {
                // Redirect to login if not authenticated
                window.location.href = this.loginUrl;
                return false;
            }
            
            // User is authenticated
            console.log('Welcome,', data.user.username);
            
            // Update navbar with user info if element exists
            if (this.showWelcomeMessage) {
                const navbar = document.querySelector('.navbar-logo');
                if (navbar) {
                    navbar.textContent = `GabayAI - Welcome, ${data.user.username}`;
                }
            }
            
            return data.user;
        } catch (error) {
            console.error('Session check error:', error);
            window.location.href = this.loginUrl;
            return false;
        }
    }

    // Initialize authentication check on page load
    init() {
        document.addEventListener('DOMContentLoaded', () => {
            this.checkAuthentication();
        });
    }

    // Logout functionality
    logout() {
        if (confirm('Are you sure you want to logout?')) {
            // Show loading state
            const logoutBtns = document.querySelectorAll('.logout-btn, .mobile-logout button');
            logoutBtns.forEach(btn => {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Logging out...';
            });

            fetch(this.logoutUrl, { method: 'POST' })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Clear any local storage if used
                        localStorage.clear();
                        sessionStorage.clear();
                        
                        // Show success message
                        this.showLogoutMessage('Logged out successfully! Redirecting to home...');
                        
                        // Redirect to landing page
                        setTimeout(() => {
                            window.location.href = this.landingUrl;
                        }, 1500);
                    } else {
                        throw new Error('Logout failed');
                    }
                })
                .catch(error => {
                    console.error('Logout error:', error);
                    // Force redirect even on error
                    localStorage.clear();
                    sessionStorage.clear();
                    window.location.href = this.landingUrl;
                });
        }
    }

    // Show logout success message
    showLogoutMessage(message) {
        const messageDiv = document.createElement('div');
        messageDiv.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #4CAF50, #45a049);
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(76, 175, 80, 0.3);
            z-index: 10000;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideInRight 0.3s ease-out;
        `;
        
        messageDiv.innerHTML = `
            <i class="fas fa-check-circle"></i>
            <span>${message}</span>
        `;
        
        document.body.appendChild(messageDiv);
        
        setTimeout(() => {
            if (messageDiv.parentNode) {
                messageDiv.parentNode.removeChild(messageDiv);
            }
        }, 3000);
    }

    
}

// Create global instance
window.authGuard = new AuthGuard();

// Global functions for backward compatibility
function logout() {
    window.authGuard.logout();
}


// Auto-initialize authentication check
window.authGuard.init();
