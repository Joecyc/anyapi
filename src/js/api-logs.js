/**
 * anyapi-logs.js
 *
 * API Log UI (Lite+ only): search, filter, pagination, payload expand, clear logs.
 */

document.addEventListener("DOMContentLoaded", function () {
  const wrap = document.getElementById("anyapi-logs");
  if (!wrap) return;

  // Free plan — no JS needed (PHP rendered upgrade wall)
  if (wrap.dataset.plan === "starter") return;

  const cfg = window.anyapiLogs || {};

  // =========================================================================
  // DOM refs
  // =========================================================================
  const searchInput = document.getElementById("al-search");
  const searchClear = document.getElementById("al-search-clear");
  const filterBtns = document.querySelectorAll(".al-filter-btn");
  const tbody = document.getElementById("al-tbody");
  const tableWrap = document.getElementById("al-table-wrap");
  const loadingEl = document.getElementById("al-loading");
  const emptyEl = document.getElementById("al-empty");
  const paginationEl = document.getElementById("al-pagination");
  const paginationInfo = document.getElementById("al-pagination-info");
  const pageNumbers = document.getElementById("al-page-numbers");
  const prevBtn = document.getElementById("al-prev");
  const nextBtn = document.getElementById("al-next");
  const clearBtn = document.getElementById("al-clear-btn");
  const clearConfirm = document.getElementById("al-clear-confirm");
  const clearYes = document.getElementById("al-clear-confirm-yes");
  const clearNo = document.getElementById("al-clear-confirm-no");

  // Stat cards
  const statTotal = document.getElementById("al-stat-total");
  const stat2xx = document.getElementById("al-stat-2xx");
  const stat4xx = document.getElementById("al-stat-4xx");
  const stat5xx = document.getElementById("al-stat-5xx");

  // =========================================================================
  // State
  // =========================================================================
  let currentPage = 1;
  let currentSearch = "";
  let currentStatus = "";
  let totalLogs = 0;
  let perPage = 10;
  let searchTimer = null;
  let loading = false;

  // =========================================================================
  // Init
  // =========================================================================
  fetchStats();
  fetchLogs();

  // =========================================================================
  // Search
  // =========================================================================
  searchInput?.addEventListener("input", () => {
    const val = searchInput.value.trim();
    searchClear && (searchClear.style.display = val ? "flex" : "none");
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
      currentSearch = val;
      currentPage = 1;
      fetchLogs();
    }, 400);
  });

  searchClear?.addEventListener("click", () => {
    searchInput.value = "";
    searchClear.style.display = "none";
    currentSearch = "";
    currentPage = 1;
    fetchLogs();
    searchInput.focus();
  });

  // =========================================================================
  // Filter buttons
  // =========================================================================
  filterBtns.forEach((btn) => {
    btn.addEventListener("click", () => {
      filterBtns.forEach((b) => b.classList.remove("active"));
      btn.classList.add("active");
      currentStatus = btn.dataset.status;
      currentPage = 1;
      fetchLogs();
    });
  });

  // =========================================================================
  // Pagination
  // =========================================================================
  prevBtn?.addEventListener("click", () => {
    if (currentPage > 1) {
      currentPage--;
      fetchLogs();
    }
  });

  nextBtn?.addEventListener("click", () => {
    const maxPage = Math.ceil(totalLogs / perPage);
    if (currentPage < maxPage) {
      currentPage++;
      fetchLogs();
    }
  });

  // =========================================================================
  // Clear logs
  // =========================================================================
  clearBtn?.addEventListener("click", () => {
    if (clearConfirm) clearConfirm.style.display = "flex";
  });

  clearNo?.addEventListener("click", () => {
    if (clearConfirm) clearConfirm.style.display = "none";
  });

  clearYes?.addEventListener("click", async () => {
    if (clearConfirm) clearConfirm.style.display = "none";
    clearYes.disabled = true;

    const fd = new FormData();
    fd.append("action", "anyapi_clear_logs");
    fd.append("nonce", cfg.nonce);

    try {
      const res = await fetch(cfg.ajax_url, { method: "POST", body: fd });
      const data = await res.json();
      if (data.success) {
        currentPage = 1;
        fetchLogs();
        fetchStats();
      }
    } catch {
    } finally {
      clearYes.disabled = false;
    }
  });

  // =========================================================================
  // Fetch stats (counts per category)
  // =========================================================================
  async function fetchStats() {
    const fd = new FormData();
    fd.append("action", "anyapi_get_log_stats");
    fd.append("nonce", cfg.nonce);

    try {
      const res = await fetch(cfg.ajax_url, { method: "POST", body: fd });
      const data = await res.json();
      if (data.success) {
        const s = data.data;
        if (statTotal) statTotal.textContent = s.total ?? "0";
        if (stat2xx) stat2xx.textContent = s["2xx"] ?? "0";
        if (stat4xx) stat4xx.textContent = s["4xx"] ?? "0";
        if (stat5xx) stat5xx.textContent = s["5xx"] ?? "0";
      }
    } catch {}
  }

  // =========================================================================
  // Fetch logs (paginated)
  // =========================================================================
  async function fetchLogs() {
    if (loading) return;
    loading = true;
    setLoadingState(true);

    const fd = new FormData();
    fd.append("action", "anyapi_get_logs");
    fd.append("nonce", cfg.nonce);
    fd.append("page", currentPage);
    fd.append("search", currentSearch);
    fd.append("status", currentStatus);

    try {
      const res = await fetch(cfg.ajax_url, { method: "POST", body: fd });
      const data = await res.json();

      if (data.success) {
        totalLogs = parseInt(data.data.total) || 0;
        renderTable(data.data.logs || []);
        renderPagination(totalLogs, data.data.page);
      } else {
        renderEmpty();
      }
    } catch {
      renderEmpty();
    } finally {
      loading = false;
      setLoadingState(false);
    }
  }

  // =========================================================================
  // Render table rows
  // =========================================================================
  function renderTable(logs) {
    if (!tbody) return;

    if (logs.length === 0) {
      renderEmpty();
      return;
    }

    showTableState("table");
    tbody.innerHTML = "";

    logs.forEach((log) => {
      const code = parseInt(log.http_code) || 0;
      const badgeClass =
        code >= 200 && code < 300
          ? "is-2xx"
          : code >= 400 && code < 500
            ? "is-4xx"
            : code >= 500
              ? "is-5xx"
              : "is-other";
      const latency = log.latency ? log.latency + "ms" : "—";
      const timeAgo = relativeTime(log.timestamp);
      const trigger = formatTrigger(log.trigger);
      const shortUrl = truncateUrl(log.api_url, 40);

      // Main row
      const tr = document.createElement("tr");
      tr.className = "al-row";
      tr.dataset.id = log.id;
      tr.innerHTML = `
        <td class="al-td--order">
          <a href="<?php echo esc_url( admin_url( 'post.php?action=edit&post=' ) ); ?>${escHtml(log.order_id)}" target="_blank" class="al-order-link">
            #${escHtml(log.order_id)}
          </a>
        </td>
        <td class="al-td--status">
          <span class="al-status-badge ${badgeClass}">${code}</span>
        </td>
        <td class="al-td--trigger">
          <span class="al-trigger-tag">${escHtml(trigger)}</span>
        </td>
        <td class="al-td--url" title="${escHtml(log.api_url)}">
          <span class="al-url-text">${escHtml(shortUrl)}</span>
        </td>
        <td class="al-td--latency ${code >= 400 ? "is-slow" : ""}">
          ${escHtml(latency)}
        </td>
        <td class="al-td--time" title="${escHtml(log.timestamp)}">
          ${escHtml(timeAgo)}
        </td>
        <td class="al-td--expand">
          <button class="al-expand-btn" type="button" data-id="${escHtml(log.id)}" aria-expanded="false">
            <span class="al-expand-icon">▶</span>
          </button>
        </td>
      `;
      tbody.appendChild(tr);

      // Payload expand row (hidden)
      const payloadRow = document.createElement("tr");
      payloadRow.className = "al-payload-row";
      payloadRow.id = `al-payload-${log.id}`;
      payloadRow.style.display = "none";

      const prettyPayload = tryPrettify(log.payload);
      payloadRow.innerHTML = `
        <td colspan="7" class="al-payload-cell">
          <div class="al-payload-inner">
            <div class="al-payload-header">
              <span class="al-payload-label">Payload</span>
              <button class="al-copy-btn" type="button" data-payload="${escAttr(log.payload)}" title="Copy payload">📋 Copy</button>
            </div>
            <pre class="al-payload-code">${escHtml(prettyPayload)}</pre>
          </div>
        </td>
      `;
      tbody.appendChild(payloadRow);
    });
  }

  // =========================================================================
  // Expand / collapse payload rows
  // =========================================================================
  tbody?.addEventListener("click", (e) => {
    // Expand toggle
    const expandBtn = e.target.closest(".al-expand-btn");
    if (expandBtn) {
      const id = expandBtn.dataset.id;
      const payloadRow = document.getElementById(`al-payload-${id}`);
      const icon = expandBtn.querySelector(".al-expand-icon");
      const expanded = expandBtn.getAttribute("aria-expanded") === "true";

      // Collapse all others
      document
        .querySelectorAll('.al-expand-btn[aria-expanded="true"]')
        .forEach((btn) => {
          if (btn !== expandBtn) {
            btn.setAttribute("aria-expanded", "false");
            btn.querySelector(".al-expand-icon").textContent = "▶";
            const otherRow = document.getElementById(
              `al-payload-${btn.dataset.id}`,
            );
            if (otherRow) otherRow.style.display = "none";
          }
        });

      expandBtn.setAttribute("aria-expanded", expanded ? "false" : "true");
      icon.textContent = expanded ? "▶" : "▼";
      if (payloadRow)
        payloadRow.style.display = expanded ? "none" : "table-row";
    }

    // Copy payload
    const copyBtn = e.target.closest(".al-copy-btn");
    if (copyBtn) {
      const text = copyBtn.dataset.payload || "";
      navigator.clipboard
        ?.writeText(tryPrettify(text))
        .then(() => {
          const orig = copyBtn.textContent;
          copyBtn.textContent = "✅ Copied";
          setTimeout(() => (copyBtn.textContent = orig), 1500);
        })
        .catch(() => {});
    }
  });

  // =========================================================================
  // Pagination render
  // =========================================================================
  function renderPagination(total, page) {
    if (!paginationEl) return;
    const maxPage = Math.ceil(total / perPage);

    if (total === 0 || maxPage <= 1) {
      paginationEl.style.display = "none";
      return;
    }

    paginationEl.style.display = "flex";
    const start = (page - 1) * perPage + 1;
    const end = Math.min(page * perPage, total);

    if (paginationInfo) {
      paginationInfo.textContent = `Showing ${start}–${end} of ${total} logs`;
    }

    // Prev / Next
    if (prevBtn) prevBtn.disabled = page <= 1;
    if (nextBtn) nextBtn.disabled = page >= maxPage;

    // Page number buttons (show up to 5 around current)
    if (pageNumbers) {
      pageNumbers.innerHTML = "";
      const range = buildPageRange(page, maxPage);
      range.forEach((p) => {
        if (p === "…") {
          const dots = document.createElement("span");
          dots.className = "al-page-dots";
          dots.textContent = "…";
          pageNumbers.appendChild(dots);
        } else {
          const btn = document.createElement("button");
          btn.className = `al-page-num ${p === page ? "is-current" : ""}`;
          btn.type = "button";
          btn.textContent = p;
          btn.addEventListener("click", () => {
            currentPage = p;
            fetchLogs();
          });
          pageNumbers.appendChild(btn);
        }
      });
    }
  }

  function buildPageRange(current, max) {
    if (max <= 7) return Array.from({ length: max }, (_, i) => i + 1);
    const range = [];
    if (current <= 4) {
      range.push(1, 2, 3, 4, 5, "…", max);
    } else if (current >= max - 3) {
      range.push(1, "…", max - 4, max - 3, max - 2, max - 1, max);
    } else {
      range.push(1, "…", current - 1, current, current + 1, "…", max);
    }
    return range;
  }

  // =========================================================================
  // UI state helpers
  // =========================================================================
  function setLoadingState(isLoading) {
    if (loadingEl) loadingEl.style.display = isLoading ? "flex" : "none";
    if (isLoading && tableWrap) tableWrap.style.opacity = "0.5";
    else if (tableWrap) tableWrap.style.opacity = "1";
  }

  function showTableState(state) {
    if (emptyEl) emptyEl.style.display = state === "empty" ? "flex" : "none";
    if (tableWrap)
      tableWrap.style.display = state === "table" ? "block" : "none";
  }

  function renderEmpty() {
    if (tbody) tbody.innerHTML = "";
    showTableState("empty");
    if (paginationEl) paginationEl.style.display = "none";
  }

  // =========================================================================
  // Helpers
  // =========================================================================
  function tryPrettify(str) {
    try {
      return JSON.stringify(JSON.parse(str), null, 2);
    } catch {
      return str || "";
    }
  }

  function escHtml(str) {
    return String(str ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  function escAttr(str) {
    return String(str ?? "").replace(/"/g, "&quot;");
  }

  function truncateUrl(url, max) {
    if (!url) return "—";
    return url.length > max ? url.slice(0, max) + "…" : url;
  }

  function formatTrigger(trigger) {
    const map = {
      watch_orders: "Watch Orders",
      watch_new_orders: "New Order",
      watch_pending_order: "Pending",
      watch_processing_order: "Processing",
      watch_on_hold_order: "On Hold",
      watch_completed_order: "Completed",
      watch_cancelled_order: "Cancelled",
      watch_refunded_order: "Refunded",
      watch_failed_order: "Failed",
    };
    return map[trigger] || trigger || "—";
  }

  function relativeTime(timestamp) {
    if (!timestamp) return "—";
    const then = new Date(timestamp.replace(" ", "T") + "Z");
    const diff = Math.floor((Date.now() - then.getTime()) / 1000);
    if (diff < 60) return "Just now";
    if (diff < 3600) return Math.floor(diff / 60) + " min ago";
    if (diff < 86400) return Math.floor(diff / 3600) + " hr ago";
    if (diff < 604800) return Math.floor(diff / 86400) + " d ago";
    return then.toLocaleDateString();
  }
});
