import express from "express";
import dotenv from "dotenv";
import path from "path";
import { fileURLToPath } from "url";
import cors from "cors";
import bodyParser from "body-parser";
import { OAuth2Client } from "google-auth-library";

dotenv.config();

const app = express();
const PORT = process.env.PORT || 5000;

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

// Serve static files from parent directory where HTML/JS are located
app.use(express.static(path.join(__dirname, '..')));
app.use(cors());
app.use(bodyParser.json());

// Initialize Google client
const googleClient = new OAuth2Client(process.env.GOOGLE_CLIENT_ID);

console.log('🚀 Starting server...');
console.log('Google Client ID:', process.env.GOOGLE_CLIENT_ID ? '✅ Found' : '❌ Missing');

// MAIN ROUTE - Serve signin.html
app.get('/', (req, res) => {
  console.log('📄 Serving main page');
  res.sendFile(path.join(__dirname, '..', 'signin.html'));
});

// CONFIG ROUTE - This was missing!
app.get('/config', (req, res) => {
  console.log('⚙️ Config route accessed');
  
  if (!process.env.GOOGLE_CLIENT_ID) {
    console.error('❌ Google Client ID missing from .env');
    return res.status(500).json({ 
      error: 'Google Client ID not configured' 
    });
  }
  
  console.log('✅ Sending Google Client ID to frontend');
  res.json({ 
    googleClientId: process.env.GOOGLE_CLIENT_ID,
    status: 'success',
    timestamp: new Date().toISOString()
  });
});

// TOKEN VERIFICATION ROUTE
app.post('/verify-token', async (req, res) => {
  try {
    const { token } = req.body;
    console.log('🔐 Verifying Google token...');
    
    const ticket = await googleClient.verifyIdToken({
      idToken: token,
      audience: process.env.GOOGLE_CLIENT_ID
    });
    
    const payload = ticket.getPayload();
    console.log('✅ Token verified for user:', payload.email);
    
    res.json({ 
      success: true,
      user: {
        googleId: payload.sub,
        email: payload.email,
        name: payload.name,
        picture: payload.picture,
        emailVerified: payload.email_verified
      }
    });
    
  } catch (error) {
    console.error('❌ Token verification failed:', error.message);
    res.status(401).json({ 
      success: false,
      error: error.message 
    });
  }
});

// HEALTH CHECK ROUTE
app.get('/health', (req, res) => {
  console.log('🏥 Health check accessed');
  res.json({ 
    status: 'healthy',
    timestamp: new Date().toISOString(),
    googleConfigured: !!process.env.GOOGLE_CLIENT_ID,
    routes: ['/', '/config', '/verify-token', '/health']
  });
});

// TEST ROUTE
app.get('/test', (req, res) => {
  console.log('🔧 Test route accessed');
  res.json({ 
    message: 'Server is working perfectly!',
    timestamp: new Date().toISOString()
  });
});

// ERROR HANDLING - Catch undefined routes
app.use((req, res) => {
  console.log('❌ Route not found:', req.url);
  res.status(404).json({ 
    error: 'Route not found',
    url: req.url,
    available: ['/', '/config', '/verify-token', '/health', '/test']
  });
});

// START SERVER
app.listen(PORT, () => {
  console.log('\n🎉 SERVER SUCCESSFULLY STARTED!');
  console.log(`✅ Main page: http://localhost:${PORT}`);
  console.log(`⚙️  Config: http://localhost:${PORT}/config`);
  console.log(`🏥 Health: http://localhost:${PORT}/health`);
  console.log(`🔧 Test: http://localhost:${PORT}/test`);
  
  if (!process.env.GOOGLE_CLIENT_ID) {
    console.log('\n⚠️  WARNING: Add GOOGLE_CLIENT_ID to your .env file');
  } else {
    console.log('\n🎯 Google Sign-In ready to use!');
  }
});
