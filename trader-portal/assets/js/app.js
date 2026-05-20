/**
 * Trader portal: mobile sidebar, Chart.js dashboard, product form AJAX, toasts.
 */
(function () {
  "use strict";

  function qs(sel, root) {
    return (root || document).querySelector(sel);
  }

  function qsa(sel, root) {
    return Array.prototype.slice.call((root || document).querySelectorAll(sel));
  }

  /* --- Mobile sidebar --- */
  var toggle = qs("#sidebarToggle");
  var sidebar = qs("#traderSidebar");
  var backdrop = qs("#sidebarBackdrop");
  if (toggle && sidebar) {
    function openSb() {
      sidebar.classList.add("open");
      if (backdrop) {
        backdrop.hidden = false;
      }
    }
    function closeSb() {
      sidebar.classList.remove("open");
      if (backdrop) {
        backdrop.hidden = true;
      }
    }
    toggle.addEventListener("click", function () {
      if (sidebar.classList.contains("open")) {
        closeSb();
      } else {
        openSb();
      }
    });
    if (backdrop) {
      backdrop.addEventListener("click", closeSb);
    }
  }

  /* --- Toast --- */
  window.showToast = function (message, type) {
    var root = qs("#toast-root");
    if (!root) {
      return;
    }
    var el = document.createElement("div");
    el.className = "toast";
    el.textContent = message;
    if (type === "error") {
      el.style.background = "#991b1b";
    }
    root.appendChild(el);
    setTimeout(function () {
      el.remove();
    }, 4200);
  };

  /* --- Dashboard Chart.js --- */
  function initDashboardChart() {
    var data = window.__DASHBOARD_CHART__;
    var canvas = qs("#chartRevenue");
    if (!data || !canvas || typeof Chart === "undefined") {
      return;
    }
    var labels = data.map(function (d) {
      return d.day;
    });
    var amounts = data.map(function (d) {
      return d.amount;
    });
    new Chart(canvas.getContext("2d"), {
      type: "bar",
      data: {
        labels: labels,
        datasets: [
          {
            label: "Revenue (USD $)",
            data: amounts,
            backgroundColor: "rgba(31, 122, 77, 0.72)",
            borderColor: "#1F7A4D",
            borderWidth: 1,
            borderRadius: 8,
            borderSkipped: false,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { display: false },
        },
        scales: {
          x: {
            grid: { display: false },
            ticks: { color: "#6B7280", font: { size: 12 } },
          },
          y: {
            beginAtZero: true,
            grid: { color: "rgba(229, 231, 235, 0.8)" },
            ticks: {
              color: "#6B7280",
              font: { size: 12 },
              callback: function (v) {
                return "$" + v;
              },
            },
          },
        },
      },
    });
  }

  /* --- Refresh stats via AJAX (optional) --- */
  function refreshStats() {
    var url = window.__API_STATS__;
    if (!url) {
      return;
    }
    fetch(url, { credentials: "same-origin" })
      .then(function (r) {
        return r.json();
      })
      .then(function (j) {
        if (!j.ok) {
          return;
        }
        var elR = qs("#statRevenue");
        var elO = qs("#statOrders");
        var elP = qs("#statProducts");
        var elS = qs("#statSlots");
        if (elR) {
          elR.textContent =
            "$" +
            Number(j.revenue || 0).toLocaleString(undefined, {
              minimumFractionDigits: 2,
              maximumFractionDigits: 2,
            });
        }
        if (elO) {
          elO.textContent = String(j.orders || 0);
        }
        if (elP) {
          elP.textContent = String(j.products || 0);
        }
        if (elS) {
          elS.textContent = String(j.slots || 0);
        }
      })
      .catch(function () {});
  }

  /* --- Product form (add / edit) --- */
  function initProductForm() {
    var form = qs("#productForm");
    if (!form) {
      return;
    }

    var tagRow = qs("#tagPillRow");
    var tagsInput = qs("#tagsField");
    var customTagInput = qs("#customTagInput");
    var addCustomTagBtn = qs("#addCustomTagBtn");

    function syncTags() {
      if (!tagsInput) {
        return;
      }
      var selected = qsa("#tagPillRow .pill-btn")
        .filter(function (b) {
          return b.classList.contains("selected");
        })
        .map(function (b) {
          return (b.getAttribute("data-tag") || b.textContent || "").trim();
        })
        .filter(Boolean);
      tagsInput.value = selected.join(",");
    }

    function tagExists(label) {
      var needle = label.toLowerCase();
      return Array.prototype.some.call(qsa("#tagPillRow .pill-btn"), function (b) {
        return ((b.getAttribute("data-tag") || b.textContent || "").trim().toLowerCase() === needle);
      });
    }

    function bindPillToggle(btn) {
      btn.addEventListener("click", function () {
        var on = !btn.classList.contains("selected");
        btn.classList.toggle("selected", on);
        btn.setAttribute("aria-pressed", on ? "true" : "false");
        syncTags();
      });
    }

    if (tagRow) {
      qsa("#tagPillRow .pill-btn").forEach(bindPillToggle);
    }

    function addCustomTag() {
      if (!customTagInput || !tagRow) {
        return;
      }
      var label = customTagInput.value.trim();
      if (label === "") {
        showToast("Enter a tag name first.", "error");
        customTagInput.focus();
        return;
      }
      if (label.length > 40) {
        showToast("Tags must be 40 characters or fewer.", "error");
        return;
      }
      if (tagExists(label)) {
        var existing = Array.prototype.find.call(qsa("#tagPillRow .pill-btn"), function (b) {
          return (b.getAttribute("data-tag") || b.textContent || "").trim().toLowerCase() === label.toLowerCase();
        });
        if (existing) {
          existing.classList.add("selected");
          existing.setAttribute("aria-pressed", "true");
          syncTags();
        }
        customTagInput.value = "";
        return;
      }
      var btn = document.createElement("button");
      btn.type = "button";
      btn.className = "pill-btn pill-btn--custom selected";
      btn.setAttribute("data-tag", label);
      btn.setAttribute("aria-pressed", "true");
      btn.textContent = label;
      bindPillToggle(btn);
      tagRow.appendChild(btn);
      customTagInput.value = "";
      syncTags();
    }

    if (addCustomTagBtn) {
      addCustomTagBtn.addEventListener("click", addCustomTag);
    }
    if (customTagInput) {
      customTagInput.addEventListener("keydown", function (e) {
        if (e.key === "Enter") {
          e.preventDefault();
          addCustomTag();
        }
      });
    }

    var dz = qs("#dropzone");
    var fileInput = qs("#imagesInput");
    var preview = qs("#imagePreview");

    if (dz && fileInput) {
      dz.addEventListener("click", function () {
        fileInput.click();
      });
      dz.addEventListener("dragover", function (e) {
        e.preventDefault();
        dz.classList.add("dragover");
      });
      dz.addEventListener("dragleave", function () {
        dz.classList.remove("dragover");
      });
      dz.addEventListener("drop", function (e) {
        e.preventDefault();
        dz.classList.remove("dragover");
        appendPreviewFiles(e.dataTransfer.files);
      });
      fileInput.addEventListener("change", function () {
        appendPreviewFiles(fileInput.files);
        fileInput.value = "";
      });
    }

    /** Accumulate multiple file-picker selections on add/edit product forms. */
    var pendingUploadFiles = [];

    function appendPreviewFiles(files) {
      if (!preview || !files || !files.length) {
        return;
      }
      Array.prototype.forEach.call(files, function (f) {
        if (!f.type.match(/^image\//)) {
          return;
        }
        pendingUploadFiles.push(f);
        var fig = document.createElement("figure");
        var img = document.createElement("img");
        img.alt = "";
        img.src = URL.createObjectURL(f);
        var rm = document.createElement("button");
        rm.type = "button";
        rm.className = "remove-img";
        rm.setAttribute("aria-label", "Remove");
        rm.textContent = "×";
        rm.addEventListener("click", function () {
          URL.revokeObjectURL(img.src);
          var idx = pendingUploadFiles.indexOf(f);
          if (idx >= 0) {
            pendingUploadFiles.splice(idx, 1);
          }
          fig.remove();
          syncPendingFilesToInput();
        });
        fig.appendChild(img);
        fig.appendChild(rm);
        preview.appendChild(fig);
      });
      syncPendingFilesToInput();
    }

    function syncPendingFilesToInput() {
      if (!fileInput || typeof DataTransfer === "undefined") {
        return;
      }
      var dt = new DataTransfer();
      pendingUploadFiles.forEach(function (f) {
        dt.items.add(f);
      });
      fileInput.files = dt.files;
    }

    qsa("[data-remove-existing]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        var fig = btn.closest("[data-existing-image]");
        if (!fig) {
          return;
        }
        var hidden = fig.querySelector('input[name="keep_images[]"]');
        if (hidden) {
          hidden.remove();
        }
        fig.remove();
      });
    });

    function renderPreview(files) {
      if (!preview) {
        return;
      }
      preview.innerHTML = "";
      if (!files || !files.length) {
        return;
      }
      Array.prototype.forEach.call(files, function (f, idx) {
        if (!f.type.match(/^image\//)) {
          return;
        }
        var fig = document.createElement("figure");
        var img = document.createElement("img");
        img.alt = "";
        img.src = URL.createObjectURL(f);
        var rm = document.createElement("button");
        rm.type = "button";
        rm.className = "remove-img";
        rm.setAttribute("aria-label", "Remove");
        rm.textContent = "×";
        rm.addEventListener("click", function () {
          URL.revokeObjectURL(img.src);
          fig.remove();
        });
        fig.appendChild(img);
        fig.appendChild(rm);
        preview.appendChild(fig);
      });
    }

    function validateProductPricing() {
      var priceEl = qs("#price");
      var stockEl = qs("#stock");
      var maxEl = qs("#max_per_order");

      if (!priceEl) {
        return true;
      }

      var price = parseFloat(priceEl.value, 10);
      if (isNaN(price) || price <= 0 || price > 9999.99) {
        showToast("Price must be greater than $0 and at most $9,999.99.", "error");
        priceEl.focus();
        return false;
      }

      if (stockEl) {
        var stock = parseInt(stockEl.value, 10);
        if (isNaN(stock) || stock < 0 || stock > 9999) {
          showToast("Stock available must be between 0 and 9,999.", "error");
          stockEl.focus();
          return false;
        }
      }

      if (maxEl) {
        var maxOrder = parseInt(maxEl.value, 10);
        if (isNaN(maxOrder) || maxOrder < 1 || maxOrder > 20) {
          showToast("Max per order must be between 1 and 20.", "error");
          maxEl.focus();
          return false;
        }
      }

      return true;
    }

    function sendProduct(statusVal, after) {
      if (!validateProductPricing()) {
        return;
      }
      var st = qs("#statusField");
      if (st) {
        st.value = statusVal;
      }
      syncTags();
      syncPendingFilesToInput();
      var fd = new FormData(form);
      var action = form.getAttribute("action") || "";
      var csrfMeta = qs('meta[name="csrf-token"]');
      var csrf = csrfMeta ? csrfMeta.getAttribute("content") : "";
      fetch(action, {
        method: "POST",
        body: fd,
        credentials: "same-origin",
        headers: csrf ? { "X-CSRF-TOKEN": csrf } : {},
      })
        .then(function (r) {
          return r.json();
        })
        .then(function (j) {
          if (j.ok) {
            showToast("Product saved.", "ok");
            if (typeof after === "function") {
              after();
            } else if (form.dataset.redirectPublished) {
              window.location.href = form.dataset.redirectPublished;
            }
          } else {
            showToast(j.error || "Save failed", "error");
          }
        })
        .catch(function () {
          showToast("Network error", "error");
        });
    }

    qsa("[data-product-action]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        var act = btn.getAttribute("data-product-action");
        if (act === "publish") {
          sendProduct("published", function () {
            if (form.dataset.redirectPublished) {
              window.location.href = form.dataset.redirectPublished;
            }
          });
        } else if (act === "draft") {
          sendProduct("draft", null);
        } else if (act === "new") {
          sendProduct(
            qs("#statusField") ? qs("#statusField").value || "published" : "published",
            function () {
              form.reset();
              if (preview) {
                preview.innerHTML = "";
              }
              qsa("#tagPillRow .pill-btn").forEach(function (b) {
                b.classList.remove("selected");
                b.setAttribute("aria-pressed", "false");
              });
              if (customTagInput) {
                customTagInput.value = "";
              }
              syncTags();
            }
          );
        }
      });
    });

    form.addEventListener("submit", function (e) {
      e.preventDefault();
      var statusField = qs("#statusField");
      var statusVal = statusField ? statusField.value || "published" : "published";
      sendProduct(statusVal, function () {
        if (form.dataset.redirectPublished) {
          window.location.href = form.dataset.redirectPublished;
        }
      });
    });
  }

  function initToggleProducts() {
    qsa(".btn-toggle-product").forEach(function (btn) {
      btn.addEventListener("click", function () {
        var url = btn.getAttribute("data-toggle-url");
        var pid = btn.getAttribute("data-product-id");
        var action = btn.getAttribute("data-action");
        var csrfMeta = qs('meta[name="csrf-token"]');
        var csrf = csrfMeta ? csrfMeta.getAttribute("content") : "";
        var fd = new FormData();
        fd.append("product_id", pid);
        fd.append("action", action);
        fd.append("_csrf", csrf);
        fetch(url, {
          method: "POST",
          body: fd,
          credentials: "same-origin",
          headers: csrf ? { "X-CSRF-TOKEN": csrf } : {},
        })
          .then(function (r) {
            return r.json();
          })
          .then(function (j) {
            if (j.ok) {
              showToast("Product updated.");
              window.location.reload();
            } else {
              showToast(j.error || "Update failed", "error");
            }
          })
          .catch(function () {
            showToast("Network error", "error");
          });
      });
    });
  }

  function initOrderStatusForm() {
    var form = qs("#orderStatusForm");
    if (!form) {
      return;
    }
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      var csrfMeta = qs('meta[name="csrf-token"]');
      var csrf = csrfMeta ? csrfMeta.getAttribute("content") : "";
      var fd = new FormData(form);
      var url = form.getAttribute("data-update-url") || "";
      if (!url) {
        return;
      }
      fetch(url, {
        method: "POST",
        body: fd,
        credentials: "same-origin",
        headers: csrf ? { "X-CSRF-TOKEN": csrf } : {},
      })
        .then(function (r) {
          return r.json();
        })
        .then(function (j) {
          if (j.ok) {
            showToast("Order status updated.");
            window.location.reload();
          } else {
            showToast(j.error || "Update failed", "error");
          }
        })
        .catch(function () {
          showToast("Network error", "error");
        });
    });
  }

  function initDeleteButtons() {
    qsa(".btn-delete-product").forEach(function (btn) {
      btn.addEventListener("click", function () {
        if (!confirm("Delete this product?")) {
          return;
        }
        var url = btn.getAttribute("data-delete-url");
        var pid = btn.getAttribute("data-product-id");
        var csrfMeta = qs('meta[name="csrf-token"]');
        var csrf = csrfMeta ? csrfMeta.getAttribute("content") : "";
        var fd = new FormData();
        fd.append("product_id", pid);
        fd.append("_csrf", csrf);
        fetch(url, {
          method: "POST",
          body: fd,
          credentials: "same-origin",
          headers: csrf ? { "X-CSRF-TOKEN": csrf } : {},
        })
          .then(function (r) {
            return r.json();
          })
          .then(function (j) {
            if (j.ok) {
              showToast("Product deleted.");
              window.location.reload();
            } else {
              showToast(j.error || "Delete failed", "error");
            }
          })
          .catch(function () {
            showToast("Network error", "error");
          });
      });
    });
  }

  document.addEventListener("DOMContentLoaded", function () {
    initDashboardChart();
    refreshStats();
    initProductForm();
    initDeleteButtons();
    initToggleProducts();
    initOrderStatusForm();
  });
})();
