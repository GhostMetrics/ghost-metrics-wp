import "../scss/index.scss";

document.addEventListener("DOMContentLoaded", function () {
  const embedModeSelect = document.querySelector("#embed_mode");
  const advancedOptions = document.querySelector(".advanced-options");
  const containerOptions = document.querySelector(".tag-manager-container");
  const authTokenField = document.querySelector(
    "[name='ghost-metrics-wp_auth_token']",
  );
  const enableAuthButton = document.querySelector("#enable-auth-token");

  // Toggle settings based on embed mode
  function toggleSettings() {
    const selectedMode = embedModeSelect ? embedModeSelect.value : "regular";

    if (selectedMode === "regular") {
      advancedOptions.style.display = "block";
      containerOptions.style.display = "none";
    } else if (selectedMode === "tag_manager") {
      containerOptions.style.display = "block";
      advancedOptions.style.display = "none";
    }
  }

  // Enable auth token field when "Edit Token" is clicked
  if (enableAuthButton) {
    enableAuthButton.addEventListener("click", function () {
      authTokenField.disabled = false;
      authTokenField.value = "";
      authTokenField.focus();
    });
  }

  // Initial load
  if (embedModeSelect) {
    toggleSettings();
    embedModeSelect.addEventListener("change", toggleSettings);
  }

  // Custom logging function
  function logMessage(message) {
    if (window.wp && wp.notifications) {
      wp.notifications.add("ghost-metrics-log", {
        title: "Ghost Metrics",
        message,
        type: "info",
      });
    } else {
      console.info(message); // eslint-disable-line no-console
    }
  }

  // Check if ghostMetricsData is defined
  if (typeof ghostMetricsData !== "undefined") {
    logMessage("Ghost Metrics data loaded successfully.");
  } else {
    logMessage("Ghost Metrics data is missing.");
  }
});
