/*=============== SHOW MENU ===============*/
const navMenu = document.getElementById('nav-menu'),
      navToggle = document.getElementById('nav-toggle'),
      navClose = document.getElementById('nav-close');

/* Menu show */
navToggle.addEventListener('click', () => {
   navMenu.classList.add('show-menu');
});

/* Menu hidden */
navClose.addEventListener('click', () => {
   navMenu.classList.remove('show-menu');
});

/*=============== SEARCH ===============*/
const search = document.getElementById('search'),
      searchBtn = document.getElementById('search-btn'),
      searchClose = document.getElementById('search-close');

/* Search show */
searchBtn.addEventListener('click', () => {
   search.classList.add('show-search');
});

/* Search hidden */
searchClose.addEventListener('click', () => {
   search.classList.remove('show-search');
});

/*=============== LOGIN ===============*/
const login = document.getElementById('login'),
      loginBtn = document.getElementById('login-btn'),
      loginClose = document.getElementById('login-close');

// Show login modal
loginBtn.addEventListener("click", () => {
    console.log("Login modal opened");
    login.classList.add("show-login");
    login.classList.remove("hidden");
});

// Close login modal
loginClose.addEventListener("click", () => {
    console.log("Login modal closed");
    login.classList.remove("show-login");
    login.classList.add("hidden");
});

// Optional: Close modal if clicking outside it
document.addEventListener("click", (e) => {
    if (!login.contains(e.target) && e.target !== loginBtn) {
        login.classList.remove("show-login");
        login.classList.add("hidden");
    }
});

/*=============== TOGGLE LOGIN/SIGNUP FORMS ===============*/
const loginForm = document.getElementById('login-form');
const signupForm = document.getElementById('signup-form');
const showSignup = document.getElementById('show-signup');
const showLogin = document.getElementById('show-login');

// Show Sign-Up Form
showSignup.addEventListener('click', (e) => {
    e.preventDefault();
    loginForm.classList.add('hidden');  // Hide Login Form
    signupForm.classList.remove('hidden');  // Show Sign-Up Form
});

// Show Login Form
showLogin.addEventListener('click', (e) => {
    e.preventDefault();
    signupForm.classList.add('hidden');  // Hide Sign-Up Form
    loginForm.classList.remove('hidden');  // Show Login Form
});

/*=============== USER DROPDOWN MENU ===============*/
const userIcon = document.getElementById("user-icon");
const userDropdown = document.getElementById("user-dropdown");

if (userIcon) {
    userIcon.addEventListener("click", () => {
        userDropdown.classList.toggle("hidden");
    });
}

// Optional: Close the dropdown if clicking outside
document.addEventListener("click", (e) => {
    if (!userIcon.contains(e.target) && !userDropdown.contains(e.target)) {
        userDropdown.classList.add("hidden");
    }
});
