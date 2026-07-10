/**
 * dashboard.js
 *
 * Dashboard JS — logs, dark mode toggle, banner dismiss.
 */

document.addEventListener("DOMContentLoaded", function () {
  // == Config (from wp_localize_script) ======================================
  // anyapiDashboard: ajax_url, nonce (logs), plan, upgrade_url, i18n
  // anyapiReview:    ajax_url, nonce (review), show_banner, i18n
  const cfg = window.anyapiDashboard || {};
  const cfgRev = window.anyapiReview || {};

  if (!cfg.ajax_url) {
    console.warn(
      "[AnyAPI Dashboard] anyapiDashboard config not found. Check wp_localize_script.",
    );
    return;
  }

  // Current plan — injected by enqueueFiles()
  const IS_STARTER = cfg.plan === "starter";
  // UTM-tag the hardcoded fallback; same medium as the PlanHelper source.
  const UPGRADE_URL =
    cfg.upgrade_url ||
    "https://anyapiplugin.com/pricing?utm_source=starter&utm_medium=plugin&utm_campaign=upgrade";

  // == DOM refs ==============================================================
  const tableBody = document.getElementById("anyapi-logs-body");
  const searchEl = document.getElementById("anyapi-log-search");
  const statusEl = document.getElementById("anyapi-status-filter");
  const refreshBtn = document.getElementById("anyapi-log-refresh");
  const infoEl = document.getElementById("logs-info");
  const paginEl = document.getElementById("logs-pagination");
  const updateTime = document.getElementById("update-time");

  // tableBody guard is below dark mode + banner init — Starter has no #anyapi-logs-body.

  // == State =================================================================
  let currentPage = 1;
  let currentSearch = "";
  let currentStatus = "";
  let loading = false;
  let debounceTimer = null;

  // == DARK MODE TOGGLE ======================================================
  const THEME_KEY = "anyapi-theme";
  const DARK_CLASS = "dark-mode";

  const toggleBtn = document.getElementById("dark-mode-toggle");
  const thumbIcon = toggleBtn
    ? toggleBtn.querySelector(".dm-thumb-icon")
    : null;

  /**
   * Apply theme to <body>.
   * The CSS handles all visual transitions (spring-physics thumb via CSS variables).
   * @param {'dark'|'light'} theme
   */
  function applyTheme(theme) {
    const isDark = theme === "dark";
    document.body.classList.toggle(DARK_CLASS, isDark);
    // Update thumb emoji: sun in light, moon in dark
    if (thumbIcon) {
      thumbIcon.textContent = isDark ? "\uD83C\uDF19" : "\u2600\uFE0F";
    }
    // Accessibility: aria-pressed reflects current state
    if (toggleBtn) {
      toggleBtn.setAttribute("aria-pressed", String(isDark));
    }
  }

  /**
   * Toggle theme and save to localStorage.
   */
  function toggleTheme() {
    const next = document.body.classList.contains(DARK_CLASS)
      ? "light"
      : "dark";
    localStorage.setItem(THEME_KEY, next);
    applyTheme(next);
  }

  // Restore saved preference on page load
  applyTheme(localStorage.getItem(THEME_KEY) || "light");

  if (toggleBtn) {
    toggleBtn.addEventListener("click", toggleTheme);

    // Keyboard: space already triggers click on <button>, but guard Enter explicitly
    toggleBtn.addEventListener("keydown", function (e) {
      if (e.key === "Enter") {
        e.preventDefault();
        toggleTheme();
      }
    });
  }

  // == BANNER DISMISS — AJAX action: anyapi_dismiss_review ==================
  // close/later → 14-day snooze; "Leave a Review" → permanent dismiss
  const banner = document.getElementById("anyapi-review-banner");
  const closeBtn = document.getElementById("anyapi-review-close");
  const laterBtn = document.getElementById("anyapi-review-dismiss");
  const reviewLink = document.getElementById("anyapi-review-yes");

  /**
   * Animate banner out then remove from DOM.
   */
  function hideBanner() {
    if (!banner) return;
    banner.style.transition = "opacity 0.35s ease, transform 0.35s ease";
    banner.style.opacity = "0";
    banner.style.transform = "translateY(-10px)";
    setTimeout(function () {
      if (banner.parentNode) banner.remove();
    }, 370);
  }

  /**
   * Hide banner + fire AJAX to persist dismissal in WP options.
   * Silent fail: banner is already hidden regardless of AJAX result.
   * @param {boolean} reviewed  Pass true when user clicked the review link.
   */
  function dismissBanner(reviewed) {
    hideBanner();

    // cfgRev may be absent if anyapi-admin handle is not enqueued on this page
    if (!cfgRev.ajax_url || !cfgRev.nonce) return;

    var fd = new FormData();
    fd.append("action", "anyapi_dismiss_review");
    fd.append("nonce", cfgRev.nonce);
    if (reviewed) fd.append("reviewed", "1");

    fetch(cfgRev.ajax_url, { method: "POST", body: fd }).catch(function () {
      /* silent — banner is already gone */
    });
  }

  if (closeBtn)
    closeBtn.addEventListener("click", function () {
      dismissBanner(false);
    });
  if (laterBtn)
    laterBtn.addEventListener("click", function () {
      dismissBanner(false);
    });

  // Review link: mark reviewed (opens tab naturally via href + target="_blank")
  if (reviewLink) {
    reviewLink.addEventListener("click", function () {
      dismissBanner(true);
    });
  }

  // ==========================================================================
  // LOG SECTION GUARD — Starter uses static PHP render, no AJAX table needed
  // ==========================================================================
  // Dark mode + banner above this line always run.
  // Everything below only runs when the AJAX log table exists (Lite+).
  if (!tableBody) return;
  // ==========================================================================

  /**
   * Update #update-time after every successful fetchLogs().
   * Format: HH:MM:SS  (local time, matches WP admin convention)
   */
  function updateTimestamp() {
    if (!updateTime) return;
    var now = new Date();
    var hh = String(now.getHours()).padStart(2, "0");
    var mm = String(now.getMinutes()).padStart(2, "0");
    var ss = String(now.getSeconds()).padStart(2, "0");
    updateTime.textContent = hh + ":" + mm + ":" + ss;
  }

  /**
   * Show upgrade prompt row in log table (replaces skeleton / search results).
   * @param {string} [reason] - 'search' | 'filter'
   */
  function showUpgradePrompt(reason) {
    var label =
      reason === "filter" ? "Filter by status" : "Search across all logs";
    tableBody.innerHTML =
      '<tr><td colspan="6" class="anyapi-upgrade-row">' +
      '<div class="anyapi-upgrade-prompt">' +
      '<span class="upgrade-icon">🔒</span>' +
      '<span class="upgrade-text">' +
      label +
      " with Lite &mdash; </span>" +
      '<a class="upgrade-cta-link" href="' +
      escHtml(UPGRADE_URL) +
      '" target="_blank" rel="noopener">' +
      "Upgrade to Lite &rarr; $79/yr" +
      "</a>" +
      "</div>" +
      "</td></tr>";
    if (infoEl) infoEl.textContent = "";
    if (paginEl) paginEl.innerHTML = "";
  }

  // ==========================================================================
  // LOGS — skeleton / error / render
  // ==========================================================================

  // Skeleton loader
  function showSkeleton() {
    var html = "";
    for (var i = 0; i < 8; i++) {
      html +=
        '<tr class="skeleton-row">' +
        '<td><div class="skeleton"></div></td>' +
        '<td><div class="skeleton short"></div></td>' +
        '<td><div class="skeleton long"></div></td>' +
        '<td><div class="skeleton status"></div></td>' +
        '<td><div class="skeleton short"></div></td>' +
        '<td><div class="skeleton payload"></div></td>' +
        "</tr>";
    }
    tableBody.innerHTML = html;
  }

  function showError(message) {
    tableBody.innerHTML =
      '<tr><td colspan="6" style="text-align:center;color:#ef4444;padding:40px;">' +
      "\u26A0\uFE0F " +
      escHtml(message) +
      "</td></tr>";
  }

  // Time formatting (UTC-aware)
  function formatTime(timestamp) {
    if (!timestamp) return "\u2014";
    var then = new Date(timestamp.replace(" ", "T") + "Z");
    var diff = Math.floor((Date.now() - then.getTime()) / 1000);
    if (diff < 60) return diff + "s ago";
    if (diff < 3600) return Math.floor(diff / 60) + " min ago";
    if (diff < 86400) return Math.floor(diff / 3600) + " hr ago";
    return then.toLocaleDateString();
  }

  // Status badge
  function statusBadge(code) {
    var n = parseInt(code) || 0;
    var cls =
      n >= 200 && n < 300
        ? "success"
        : n >= 400 && n < 500
          ? "warning"
          : n >= 500
            ? "error"
            : "info";
    return (
      '<span class="status-badge ' + cls + '">' + (n || "\u2014") + "</span>"
    );
  }

  // Pagination with ellipsis (v2 pattern)
  function renderPagination(total, page) {
    var perPage = 10;
    var totalPages = Math.ceil(total / perPage);

    if (totalPages <= 1) {
      paginEl.innerHTML = "";
      return;
    }

    var html = '<div class="tablenav-pages">';
    var prev = cfg.i18n && cfg.i18n.prev ? cfg.i18n.prev : "Previous";
    var next = cfg.i18n && cfg.i18n.next ? cfg.i18n.next : "Next";

    if (page > 1) {
      html +=
        '<a class="prev-page button" data-page="' +
        (page - 1) +
        '">\u00AB ' +
        prev +
        "</a>";
    }

    var start = Math.max(1, page - 2);
    var end = Math.min(totalPages, page + 2);

    if (start > 1) {
      html += '<a class="button page-numbers" data-page="1">1</a>';
      if (start > 2) html += '<span class="dots">\u2026</span>';
    }

    for (var p = start; p <= end; p++) {
      if (p === page) {
        html += '<span class="current-page">' + p + "</span>";
      } else {
        html +=
          '<a class="button page-numbers" data-page="' + p + '">' + p + "</a>";
      }
    }

    if (end < totalPages) {
      if (end < totalPages - 1) html += '<span class="dots">\u2026</span>';
      html +=
        '<a class="button page-numbers" data-page="' +
        totalPages +
        '">' +
        totalPages +
        "</a>";
    }

    if (page < totalPages) {
      html +=
        '<a class="next-page button" data-page="' +
        (page + 1) +
        '">' +
        next +
        " \u00BB</a>";
    }

    html += "</div>";
    paginEl.innerHTML = html;

    // Click delegation
    var anchors = paginEl.querySelectorAll("a[data-page]");
    for (var j = 0; j < anchors.length; j++) {
      anchors[j].addEventListener(
        "click",
        (function (anchor) {
          return function (e) {
            e.preventDefault();
            fetchLogs(parseInt(anchor.dataset.page));
          };
        })(anchors[j]),
      );
    }
  }

  // Render table rows
  function renderTable(logs) {
    if (!logs.length) {
      var msg =
        cfg.i18n && cfg.i18n.no_logs ? cfg.i18n.no_logs : "No API logs found";
      tableBody.innerHTML =
        '<tr><td colspan="6" class="no-logs" style="text-align:center;padding:40px;color:#64748b;">' +
        msg +
        "</td></tr>";
      if (infoEl) infoEl.textContent = "0 results";
      if (paginEl) paginEl.innerHTML = "";
      return;
    }

    tableBody.innerHTML = logs
      .map(function (log) {
        var payloadRaw = log.payload || "";
        var payload =
          payloadRaw.length > 80
            ? payloadRaw.slice(0, 80) + "\u2026"
            : payloadRaw;
        return (
          "<tr>" +
          "<td><strong>" +
          formatTime(log.timestamp) +
          "</strong></td>" +
          '<td><span class="method method-post">POST</span></td>' +
          '<td class="endpoint"><code>' +
          escHtml(log.api_url || "\u2014") +
          "</code></td>" +
          "<td>" +
          statusBadge(log.http_code) +
          "</td>" +
          "<td>" +
          (log.latency ? log.latency + " ms" : "\u2014") +
          "</td>" +
          '<td class="payload"><pre>' +
          escHtml(payload) +
          "</pre></td>" +
          "</tr>"
        );
      })
      .join("");
  }

  // Main fetch
  async function fetchLogs(page) {
    page = page || 1;
    if (loading) return;
    loading = true;
    currentPage = page;
    showSkeleton();

    var fd = new FormData();
    fd.append("action", "anyapi_get_logs");
    fd.append("nonce", cfg.nonce || ""); // anyapi_logs_nonce
    fd.append("page", currentPage);
    fd.append("search", currentSearch);
    fd.append("status", currentStatus);

    try {
      var res = await fetch(cfg.ajax_url, { method: "POST", body: fd });
      var data = await res.json();

      if (!data.success) {
        if (data.data && data.data.code === "upgrade_required") {
          showUpgradePrompt("search");
          return;
        }
        var errMsg =
          data.data && data.data.message
            ? data.data.message
            : cfg.i18n && cfg.i18n.error
              ? cfg.i18n.error
              : "Error loading logs";
        showError(errMsg);
        return;
      }

      var logs = data.data.logs || [];
      var total = parseInt(data.data.total) || 0;
      var perPage = 10;

      renderTable(logs);

      if (total > 0 && infoEl) {
        var from = (currentPage - 1) * perPage + 1;
        var to = Math.min(currentPage * perPage, total);
        infoEl.textContent =
          "Showing " + from + "\u2013" + to + " of " + total + " results";
      }

      renderPagination(total, currentPage);

      updateTimestamp();
    } catch (err) {
      showError("Network error: " + err.message);
    } finally {
      loading = false;
    }
  }

  // Search + filter (debounced 500ms)
  function onFilterChange(reason) {
    if (IS_STARTER) {
      showUpgradePrompt(reason || "search");
      return;
    }
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(function () {
      currentSearch = searchEl ? searchEl.value.trim() : "";
      currentStatus = statusEl ? statusEl.value : "";
      currentPage = 1;
      fetchLogs(1);
    }, 500);
  }

  if (searchEl)
    searchEl.addEventListener("input", function () {
      onFilterChange("search");
    });
  if (statusEl)
    statusEl.addEventListener("change", function () {
      onFilterChange("filter");
    });

  // Refresh button
  if (refreshBtn) {
    refreshBtn.addEventListener("click", function () {
      refreshBtn.classList.add("rotating");
      fetchLogs(currentPage).finally(function () {
        setTimeout(function () {
          refreshBtn.classList.remove("rotating");
        }, 600);
      });
    });
  }

  // XSS escape helper
  function escHtml(str) {
    return String(str == null ? "" : str)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  if (!IS_STARTER) {
    fetchLogs(1);
  }

  // Auto-refresh every 60s — skip when tab is not visible or Starter plan
  setInterval(function () {
    if (!IS_STARTER && !loading && document.visibilityState === "visible") {
      fetchLogs(currentPage);
    }
  }, 60000);
});
