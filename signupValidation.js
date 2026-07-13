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

function validateSignup() {
    clearError("email");
    clearError("username");
    clearError("password");
    clearError("confirm_Password");

    let email = document.getElementById("email").value.trim();
    let username = document.getElementById("username").value.trim();
    let password = document.getElementById("password").value;
    let confirmPassword =
        document.getElementById("confirm_Password").value;

    let emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    let passwordPattern =
        /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&]).{8,}$/;

    let isValid = true;

    if (email == "") {
        showError("email", "Email is required.");
        isValid = false;
    } else if (!emailPattern.test(email)) {
        showError("email", "Please enter a valid email address.");
        isValid = false;
    }

    if (username == "") {
        showError("username", "Username is required.");
        isValid = false;
    } else if (username.length < 4) {
        showError(
            "username",
            "Username must be at least 4 characters long."
        );
        isValid = false;
    }

    if (password == "") {
        showError("password", "Password is required.");
        isValid = false;
    } else if (!passwordPattern.test(password)) {
        showError(
            "password",
            "Password must contain uppercase, lowercase, number and special character."
        );
        isValid = false;
    }

    if (confirmPassword == "") {
        showError(
            "confirm_Password",
            "Confirm Password is required."
        );
        isValid = false;
    } else if (password != confirmPassword) {
        showError(
            "confirm_Password",
            "Password and Confirm Password do not match."
        );
        isValid = false;
    }

    return isValid;
}