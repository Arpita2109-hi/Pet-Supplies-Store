function showError(inputId, message) {
    let input = document.getElementById(inputId);
    let error = document.getElementById(inputId + "_error");

    error.textContent = message;
    input.classList.add("invalid");
}

function clearError(inputId) {
    let input = document.getElementById(inputId);
    let error = document.getElementById(inputId + "_error");

    error.textContent = "";
    input.classList.remove("invalid");
}

function validateSignin() {
    clearError("login_email");
    clearError("login_password");

    let email =
        document.getElementById("login_email").value.trim();

    let password =
        document.getElementById("login_password").value;

    let emailPattern =
        /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    let isValid = true;

    if (email == "") {
        showError("login_email", "Email is required.");
        isValid = false;
    } else if (!emailPattern.test(email)) {
        showError(
            "login_email",
            "Please enter a valid email address."
        );
        isValid = false;
    }

    if (password == "") {
        showError(
            "login_password",
            "Password is required."
        );
        isValid = false;
    }

    return isValid;
}