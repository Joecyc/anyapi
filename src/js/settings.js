/**
 * settings.js
 *
 * Handles AJAX save for General Settings toggles. No page reload — immediate feedback.
 */

document.addEventListener("DOMContentLoaded", function () {
  const wrap = document.getElementById("anyapi-settings");
  if (!wrap) return;

  const cfg = window.anyapiSettings || {};
  const saveBtn = document.getElementById("st-save-btn");
  const statusEl = document.getElementById("st-form-status");

  if (!saveBtn) return;

  saveBtn.addEventListener("click", async () => {
    saveBtn.disabled = true;
    saveBtn.textContent = "Saving…";
    if (statusEl) {
      statusEl.textContent = "";
      statusEl.className = "st-form-status";
    }

    const fd = new FormData();
    fd.append("action", "anyapi_save_general_settings");
    fd.append("nonce", cfg.nonce);
    fd.append(
      "anyapi_debug_mode",
      document.getElementById("st-debug")?.checked ? "1" : "0",
    );
    fd.append(
      "anyapi_clean_uninstall",
      document.getElementById("st-clean")?.checked ? "1" : "0",
    );

    try {
      const res = await fetch(cfg.ajax_url, { method: "POST", body: fd });
      const data = await res.json();

      if (data.success) {
        showStatus("✓ " + (cfg.i18n?.saved || "Settings saved."), "is-ok");
      } else {
        showStatus("✗ " + (data.data?.message || "Failed to save."), "is-err");
      }
    } catch (err) {
      showStatus("✗ Network error.", "is-err");
    } finally {
      saveBtn.disabled = false;
      saveBtn.textContent = cfg.i18n?.save_btn || "Save Settings";
    }
  });

  function showStatus(msg, cls) {
    if (!statusEl) return;
    statusEl.textContent = msg;
    statusEl.className = "st-form-status " + cls;
    setTimeout(() => {
      statusEl.textContent = "";
      statusEl.className = "st-form-status";
    }, 3000);
  }
});
