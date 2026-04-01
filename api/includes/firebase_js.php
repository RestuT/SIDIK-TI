<?php
/**
 * Firebase JavaScript SDK Initialization
 * This file translates the project configuration into a usable JS module.
 */
?>
<!-- Firebase SDK -->
<script type="importmap">
  {
    "imports": {
      "firebase/app": "https://www.gstatic.com/firebasejs/11.0.1/firebase-app.js",
      "firebase/analytics": "https://www.gstatic.com/firebasejs/11.0.1/firebase-analytics.js",
      "firebase/firestore": "https://www.gstatic.com/firebasejs/11.0.1/firebase-firestore.js"
    }
  }
</script>

<script type="module">
  import { initializeApp } from "firebase/app";
  import { getAnalytics } from "firebase/analytics";
  import { getFirestore } from "firebase/firestore";

  const firebaseConfig = {
    apiKey: "AIzaSyDaLVaF7NVvMLFvGOGRUfOG1hKkvlVHoY0",
    authDomain: "sidik-ti.firebaseapp.com",
    projectId: "sidik-ti",
    storageBucket: "sidik-ti.firebasestorage.app",
    messagingSenderId: "624701828707",
    appId: "1:624701828707:web:e76e01850834d6c6c09256",
    measurementId: "G-2MH2YZTM7D"
  };

  // Initialize Firebase
  const app = initializeApp(firebaseConfig);
  const analytics = getAnalytics(app);
  const db = getFirestore(app);

  // Make db available globally for simple scripts if needed
  window.firebaseApp = app;
  window.db = db;
  
  console.log("Firebase initialized successfully");
</script>
