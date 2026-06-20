/**
 * rest-api.js
 *
 * WooCommerce REST API Tester: endpoint picker, auth, custom headers, request body, response display.
 */

document.addEventListener("DOMContentLoaded", function () {
  const wrap = document.getElementById("anyapi-restapi");
  if (!wrap) return;

  const cfg = window.anyapiRestTool || {};

  // =========================================================================
  // DOM refs
  // =========================================================================
  const methodSelect = document.getElementById("rt-method");
  const urlInput = document.getElementById("rt-url");
  const urlError = document.getElementById("rt-url-error");
  const authTypeSelect = document.getElementById("rt-auth-type");
  const bodyGroup = document.getElementById("rt-body-group");
  const bodyTextarea = document.getElementById("rt-body");
  const bodyError = document.getElementById("rt-body-error");
  const sendBtn = document.getElementById("rt-send");
  const sendLabel = document.getElementById("rt-send-label");
  const sendIcon = document.getElementById("rt-send-icon");

  // Auth panels
  const authSaved = document.getElementById("rt-auth-saved");
  const authWc = document.getElementById("rt-auth-wc");
  const authBearer = document.getElementById("rt-auth-bearer");

  // Response panels
  const responseMeta = document.getElementById("rt-response-meta");
  const statusBadge = document.getElementById("rt-status-badge");
  const latencyEl = document.getElementById("rt-latency");
  const responseEmpty = document.getElementById("rt-response-empty");
  const responseLoading = document.getElementById("rt-response-loading");
  const responseContent = document.getElementById("rt-response-content");
  const responseBody = document.getElementById("rt-response-body");
  const responseSize = document.getElementById("rt-response-size");
  const copyBtn = document.getElementById("rt-copy-response");
  const headersTable = document.getElementById("rt-response-headers");

  // Headers collapsible
  const headersToggle = document.getElementById("rt-headers-toggle");
  const headersBody = document.getElementById("rt-headers-body");
  const headersList = document.getElementById("rt-headers-list");
  const headersCount = document.getElementById("rt-headers-count");
  const addHeaderBtn = document.getElementById("rt-add-header");

  // =========================================================================
  // State
  // =========================================================================
  let sending = false;
  let rawResponse = "";
  let prettyResponse = "";
  let currentView = "pretty";
  let headerRows = []; // [{ id, key, value }]

  // =========================================================================
  // Quick-pick
  // =========================================================================
  document.querySelectorAll(".rt-quickpick__btn").forEach((btn) => {
    btn.addEventListener("click", () => {
      methodSelect.value = btn.dataset.method;
      urlInput.value = btn.dataset.path;
      syncMethodUI();
      urlInput.focus();

      // Highlight selected
      document
        .querySelectorAll(".rt-quickpick__btn")
        .forEach((b) => b.classList.remove("is-selected"));
      btn.classList.add("is-selected");
    });
  });

  // =========================================================================
  // Method select → update URL border colour + body visibility
  // =========================================================================
  methodSelect.addEventListener("change", syncMethodUI);

  function syncMethodUI() {
    const method = methodSelect.value;
    // Remove all method classes from select
    methodSelect.className = methodSelect.className
      .replace(/is-\w+/g, "")
      .trim();
    methodSelect.classList.add("is-" + method.toLowerCase());

    // Body: only relevant for POST / PUT / PATCH
    const showBody = ["POST", "PUT", "PATCH"].includes(method);
    if (bodyGroup) bodyGroup.style.display = showBody ? "block" : "none";
  }
  syncMethodUI(); // run once on load

  // =========================================================================
  // Auth type switcher
  // =========================================================================
  authTypeSelect?.addEventListener("change", () => {
    const type = authTypeSelect.value;
    authSaved?.style &&
      (authSaved.style.display = type === "saved" ? "block" : "none");
    authWc?.style &&
      (authWc.style.display = type === "wc_basic" ? "grid" : "none");
    authBearer?.style &&
      (authBearer.style.display = type === "bearer" ? "block" : "none");
  });

  // =========================================================================
  // Eye (reveal) buttons
  // =========================================================================
  wrap.addEventListener("click", (e) => {
    const btn = e.target.closest(".rt-eye-btn");
    if (!btn) return;
    const input = document.getElementById(btn.dataset.target);
    if (!input) return;
    const shown = input.type === "text";
    input.type = shown ? "password" : "text";
    btn.textContent = shown ? "👁" : "🙈";
  });

  // =========================================================================
  // Custom Headers — add / remove rows
  // =========================================================================
  headersToggle?.addEventListener("click", () => {
    const expanded = headersToggle.getAttribute("aria-expanded") === "true";
    headersToggle.setAttribute("aria-expanded", expanded ? "false" : "true");
    headersToggle.querySelector(".rt-collapsible__icon").textContent = expanded
      ? "▶"
      : "▼";
    if (headersBody) headersBody.style.display = expanded ? "none" : "block";
    if (!expanded && headerRows.length === 0) addHeaderRow();
  });

  addHeaderBtn?.addEventListener("click", () => addHeaderRow());

  function addHeaderRow(key = "", value = "") {
    const id = "hdr_" + Date.now() + Math.random().toString(36).slice(2, 6);
    headerRows.push({ id, key, value });

    const row = document.createElement("div");
    row.className = "rt-kv-row";
    row.dataset.id = id;
    row.innerHTML = `
      <input type="text"  class="rt-input rt-input--sm rt-kv-key"   placeholder="Header-Name"  value="${escHtml(key)}"   autocomplete="off">
      <input type="text"  class="rt-input rt-input--sm rt-kv-value" placeholder="value"         value="${escHtml(value)}" autocomplete="off">
      <button class="rt-kv-remove" type="button" data-id="${id}" title="Remove">×</button>
    `;
    headersList?.appendChild(row);
    updateHeaderCount();
  }

  headersList?.addEventListener("click", (e) => {
    const btn = e.target.closest(".rt-kv-remove");
    if (!btn) return;
    const id = btn.dataset.id;
    headerRows = headerRows.filter((r) => r.id !== id);
    btn.closest(".rt-kv-row")?.remove();
    updateHeaderCount();
  });

  function updateHeaderCount() {
    const filled = headersList?.querySelectorAll(".rt-kv-row").length || 0;
    if (headersCount) {
      headersCount.textContent = filled;
      headersCount.style.display = filled > 0 ? "inline-flex" : "none";
    }
  }

  function getCustomHeaders() {
    const headers = {};
    headersList?.querySelectorAll(".rt-kv-row").forEach((row) => {
      const k = row.querySelector(".rt-kv-key")?.value.trim();
      const v = row.querySelector(".rt-kv-value")?.value.trim();
      if (k && v) headers[k] = v;
    });
    return headers;
  }

  // =========================================================================
  // Body editor — Format + Clear
  // =========================================================================
  document.getElementById("rt-body-format")?.addEventListener("click", () => {
    if (!bodyTextarea) return;
    try {
      const parsed = JSON.parse(bodyTextarea.value);
      bodyTextarea.value = JSON.stringify(parsed, null, 2);
      clearError(bodyError);
    } catch {
      showError(bodyError, "Invalid JSON — cannot format.");
    }
  });

  document.getElementById("rt-body-clear")?.addEventListener("click", () => {
    if (bodyTextarea) bodyTextarea.value = "";
    clearError(bodyError);
  });

  // =========================================================================
  // Response tabs
  // =========================================================================
  wrap.addEventListener("click", (e) => {
    const tab = e.target.closest(".rt-tab");
    if (!tab) return;
    const tabName = tab.dataset.tab;

    document.querySelectorAll(".rt-tab").forEach((t) => {
      t.classList.toggle("active", t.dataset.tab === tabName);
      t.setAttribute(
        "aria-selected",
        t.dataset.tab === tabName ? "true" : "false",
      );
    });

    document.getElementById("rt-tab-body").style.display =
      tabName === "body" ? "block" : "none";
    document.getElementById("rt-tab-headers").style.display =
      tabName === "headers" ? "block" : "none";
  });

  // Pretty / Raw toggle
  wrap.addEventListener("click", (e) => {
    const btn = e.target.closest(".rt-view-btn");
    if (!btn) return;
    const view = btn.dataset.view;
    currentView = view;

    document
      .querySelectorAll(".rt-view-btn")
      .forEach((b) => b.classList.toggle("active", b.dataset.view === view));

    if (responseBody) {
      responseBody.textContent =
        view === "pretty" ? prettyResponse : rawResponse;
    }
  });

  // =========================================================================
  // Copy response
  // =========================================================================
  copyBtn?.addEventListener("click", async () => {
    const text = currentView === "pretty" ? prettyResponse : rawResponse;
    try {
      await navigator.clipboard.writeText(text);
      const orig = copyBtn.textContent;
      copyBtn.textContent = "✅";
      setTimeout(() => (copyBtn.textContent = orig), 1500);
    } catch {
      // fallback silent fail
    }
  });

  // =========================================================================
  // Send Request
  // =========================================================================
  sendBtn?.addEventListener("click", sendRequest);

  async function sendRequest() {
    if (sending) return;

    // ── Validate ────────────────────────────────────────────────────────────
    clearError(urlError);
    clearError(bodyError);

    const url = urlInput?.value.trim();
    if (!url) {
      showError(urlError, "Endpoint URL is required.");
      urlInput?.focus();
      return;
    }

    const method = methodSelect?.value || "GET";
    const body = bodyTextarea?.value.trim() || "";

    if (body && ["POST", "PUT", "PATCH"].includes(method)) {
      try {
        JSON.parse(body);
      } catch {
        showError(bodyError, "Invalid JSON in request body.");
        return;
      }
    }

    // ── Auth ─────────────────────────────────────────────────────────────
    const authType = authTypeSelect?.value || "none";
    let authData = { type: authType };

    if (authType === "saved") {
      const savedKey = document.getElementById("rt-saved-key")?.value;
      if (!savedKey) {
        showError(urlError, "Please select a saved API Key.");
        return;
      }
      authData.key_id = savedKey;
    } else if (authType === "wc_basic") {
      authData.consumer_key = document.getElementById("rt-ck")?.value.trim();
      authData.consumer_secret = document.getElementById("rt-cs")?.value.trim();
    } else if (authType === "bearer") {
      authData.token = document.getElementById("rt-bearer-token")?.value.trim();
    }

    // ── UI → loading state ───────────────────────────────────────────────
    sending = true;
    setSendingState(true);
    showResponseState("loading");

    // ── POST to AJAX ─────────────────────────────────────────────────────
    const startTime = performance.now();

    const fd = new FormData();
    fd.append("action", "anyapi_rest_request");
    fd.append("nonce", cfg.nonce || "");
    fd.append("method", method);
    fd.append("url", url);
    fd.append("body", body);
    fd.append("auth", JSON.stringify(authData));
    fd.append("headers", JSON.stringify(getCustomHeaders()));

    try {
      const res = await fetch(cfg.ajax_url || "/wp-admin/admin-ajax.php", {
        method: "POST",
        body: fd,
      });
      const data = await res.json();
      const elapsed = Math.round(performance.now() - startTime);

      if (data.success) {
        renderResponse(data.data, elapsed);
      } else {
        // Show actual error message from PHP (WP_Error message, validation error, etc.)
        const errMsg =
          data.data?.message || data.data?.body || "Request failed.";
        renderError(errMsg, elapsed);
      }
    } catch (err) {
      renderError("Network error: " + err.message, 0);
    } finally {
      sending = false;
      setSendingState(false);
    }
  }

  // =========================================================================
  // Render response
  // =========================================================================

  function renderResponse(data, elapsed) {
    const code = data.http_code || 0;
    rawResponse = data.body || "";
    prettyResponse = tryPrettify(rawResponse);
    currentView = "pretty";

    // Status badge
    const badgeClass =
      code >= 200 && code < 300
        ? "is-2xx"
        : code >= 400 && code < 500
          ? "is-4xx"
          : code >= 500
            ? "is-5xx"
            : "is-other";

    if (statusBadge) {
      statusBadge.textContent = code;
      statusBadge.className = `rt-status-badge ${badgeClass}`;
    }
    if (latencyEl) latencyEl.textContent = elapsed + " ms";
    if (responseSize)
      responseSize.textContent = formatBytes(rawResponse.length);

    // Body
    if (responseBody) responseBody.textContent = prettyResponse;

    // Sync view toggle
    document
      .querySelectorAll(".rt-view-btn")
      .forEach((b) =>
        b.classList.toggle("active", b.dataset.view === "pretty"),
      );

    // Response headers
    if (headersTable && data.headers) {
      headersTable.innerHTML = Object.entries(data.headers)
        .map(
          ([k, v]) => `
          <div class="rt-header-row">
            <span class="rt-header-key">${escHtml(k)}</span>
            <span class="rt-header-val">${escHtml(Array.isArray(v) ? v.join(", ") : v)}</span>
          </div>`,
        )
        .join("");
    }

    showResponseState("content");
    if (responseMeta) responseMeta.style.display = "flex";
    if (copyBtn) copyBtn.style.display = "inline-flex";
  }

  function renderError(message, elapsed) {
    rawResponse = message;
    prettyResponse = message;

    if (statusBadge) {
      statusBadge.textContent = "ERR";
      statusBadge.className = "rt-status-badge is-err";
    }
    if (latencyEl) latencyEl.textContent = elapsed ? elapsed + " ms" : "";
    if (responseBody) responseBody.textContent = message;
    if (responseSize) responseSize.textContent = "";

    showResponseState("content");
    if (responseMeta) responseMeta.style.display = "flex";
    if (copyBtn) copyBtn.style.display = "inline-flex";
  }

  function showResponseState(state) {
    responseEmpty &&
      (responseEmpty.style.display = state === "empty" ? "flex" : "none");
    responseLoading &&
      (responseLoading.style.display = state === "loading" ? "flex" : "none");
    responseContent &&
      (responseContent.style.display = state === "content" ? "block" : "none");
  }

  function setSendingState(isSending) {
    if (!sendBtn) return;
    sendBtn.disabled = isSending;
    sendBtn.classList.toggle("is-loading", isSending);
    if (sendLabel)
      sendLabel.textContent = isSending ? "Sending…" : "Send Request";
    if (sendIcon) sendIcon.textContent = isSending ? "⏳" : "▶";
  }

  // =========================================================================
  // Helpers
  // =========================================================================

  function tryPrettify(str) {
    try {
      return JSON.stringify(JSON.parse(str), null, 2);
    } catch {
      return str;
    }
  }

  function formatBytes(bytes) {
    if (bytes < 1024) return bytes + " B";
    return (bytes / 1024).toFixed(1) + " KB";
  }

  function escHtml(str) {
    return String(str)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function showError(el, msg) {
    if (el) el.textContent = msg;
  }

  function clearError(el) {
    if (el) el.textContent = "";
  }
});
