(function () {
  "use strict";

  var STORAGE_KEY_DATA = "travelbookings_admin_data_v1";

  var INITIAL_DATA = {
    customers: [],
    flights: [],
    hotels: [],
    loans: [],
    destinations: [],
    transactions: [],
    activities: [],
    monthly: [],
    counts: {},
  };

  function loadData() {
    try {
      localStorage.removeItem(STORAGE_KEY_DATA);
    } catch (e) {}

    if (window.CJ_ADMIN_DATA && Array.isArray(window.CJ_ADMIN_DATA.customers)) {
      return window.CJ_ADMIN_DATA;
    }
    return JSON.parse(JSON.stringify(INITIAL_DATA));
  }

  function saveData() {
    // State persistence synchronized via MySQL real-time API
  }

  function syncLiveMySQLData() {
    var url = (window.CJ_API_URLS && window.CJ_API_URLS.liveData) || "/admin/api/live-data";
    fetch(url, {
      headers: {
        "Accept": "application/json",
        "X-Requested-With": "XMLHttpRequest"
      }
    })
    .then(function(res) { return res.json(); })
    .then(function(res) {
      if (res && res.success && res.data) {
        DATA = res.data;
        refreshAfterChange();
      }
    })
    .catch(function(err) {
      console.warn("Could not sync live MySQL data:", err);
    });
  }

  var DATA = loadData();
  var apiState = {
    editingId: null,
    editingCategory: "Flight",
    deleteId: null,
    testing: {},
  };

  try {
    localStorage.removeItem("travelbookings_admin_api_v1");
  } catch (e) {}

  function $(id) {
    return document.getElementById(id);
  }

  function escapeHTML(value) {
    return String(value == null ? "" : value).replace(
      /[&<>"']/g,
      function (ch) {
        return {
          "&": "&amp;",
          "<": "&lt;",
          ">": "&gt;",
          '"': "&quot;",
          "'": "&#39;",
        }[ch];
      },
    );
  }

  function formatMoney(value) {
    return new Intl.NumberFormat("en-NG", {
      style: "currency",
      currency: "NGN",
      maximumFractionDigits: 0,
    }).format(value || 0);
  }

  function shortMoney(value) {
    if (value >= 1000000)
      return "₦" + (value / 1000000).toFixed(1).replace(/\.0$/, "") + "m";
    if (value >= 1000) return "₦" + Math.round(value / 1000) + "k";
    return "₦" + (value || 0);
  }

  function formatDate(iso) {
    if (!iso) return "—";
    var d = new Date(iso + "T00:00:00");
    return d.toLocaleDateString("en-GB", {
      day: "numeric",
      month: "short",
      year: "numeric",
    });
  }

  function formatDateTime(iso) {
    if (!iso) return "—";
    var d = new Date(iso);
    if (isNaN(d.getTime())) return "—";
    return d.toLocaleString("en-GB", {
      day: "2-digit",
      month: "short",
      year: "numeric",
      hour: "2-digit",
      minute: "2-digit",
    });
  }

  function initials(first, last) {
    return (String(first).charAt(0) + String(last).charAt(0)).toUpperCase();
  }

  function getCustomer(id) {
    for (var i = 0; i < DATA.customers.length; i++) {
      if (DATA.customers[i].id === id) return DATA.customers[i];
    }
    return {
      firstName: "Unknown",
      lastName: "Customer",
      email: "—",
      country: "—",
    };
  }

  function customerName(id) {
    var c = getCustomer(id);
    return c.firstName + " " + c.lastName;
  }

  function statusPill(status) {
    var map = {
      Confirmed: { cls: "status-confirmed", icon: "bi-check-circle-fill" },
      Pending: { cls: "status-pending", icon: "bi-clock-fill" },
      Completed: { cls: "status-completed", icon: "bi-check2-all" },
      Cancelled: { cls: "status-cancelled", icon: "bi-x-circle-fill" },
      "In review": { cls: "status-inreview", icon: "bi-hourglass-split" },
      "Awaiting documents": {
        cls: "status-awaiting",
        icon: "bi-file-earmark-text-fill",
      },
      "Referred to partner": { cls: "status-referred", icon: "bi-send-fill" },
      Draft: { cls: "status-draft", icon: "bi-pencil-fill" },
      Paid: { cls: "status-paid", icon: "bi-check-circle-fill" },
      Refunded: { cls: "status-refunded", icon: "bi-arrow-counterclockwise" },
      Failed: { cls: "status-failed", icon: "bi-x-circle-fill" },
      Active: { cls: "status-active", icon: "bi-check-circle-fill" },
      Inactive: { cls: "status-inactive", icon: "bi-dash-circle-fill" },
    };
    var meta = map[status] || { cls: "status-completed", icon: "bi-dot" };
    return (
      '<span class="status ' +
      meta.cls +
      '"><i class="bi ' +
      meta.icon +
      '"></i> ' +
      escapeHTML(status) +
      "</span>"
    );
  }

  function emptyRow(colspan, message) {
    return (
      '<tr class="row-empty"><td colspan="' +
      colspan +
      '" class="cell-empty">' +
      '<div class="empty-row"><i class="bi bi-inbox"></i>' +
      escapeHTML(message) +
      "</div></td></tr>"
    );
  }

  function refreshCounts() {
    var counts = {
      customers: DATA.customers.length,
      flights: DATA.flights.length,
      hotels: DATA.hotels.length,
      loans: DATA.loans.length,
      destinations: DATA.destinations.length,
      transactions: DATA.transactions.length,
      api: (DATA.apiProviders || []).length,
    };
    var badges = {
      customers: $("badgeCustomers"),
      flights: $("badgeFlights"),
      hotels: $("badgeHotels"),
      loans: $("badgeLoans"),
      destinations: $("badgeDestinations"),
      transactions: $("badgeTransactions"),
      api: $("badgeApi"),
    };
    Object.keys(badges).forEach(function (key) {
      if (badges[key]) badges[key].textContent = String(counts[key]);
    });
    return counts;
  }

  /* =========================================================
     MODAL CONTROLLERS (Details, Edit, Delete, Notifications)
     ========================================================= */
  var detailModalEl = $("detailModal");
  var detailBody = $("detailModalBody");
  var detailTitle = $("detailModalTitle");

  function findRecord(kind, id) {
    var map = {
      customer: DATA.customers,
      flight: DATA.flights,
      hotel: DATA.hotels,
      loan: DATA.loans,
      transaction: DATA.transactions,
      destination: DATA.destinations,
    };
    var list = map[kind] || [];
    for (var i = 0; i < list.length; i++) if (list[i].id === id) return list[i];
    return null;
  }

  function detailItem(label, value) {
    return (
      '<div class="detail-item"><span>' +
      escapeHTML(label) +
      "</span><strong>" +
      escapeHTML(value) +
      "</strong></div>"
    );
  }

  function openDetail(kind, id) {
    if (!detailModalEl || !detailBody || !detailTitle) return;
    var record = findRecord(kind, id);
    if (!record) return;

    var heroIcon = "bi-file-earmark-text",
      heroTitle = "",
      heroSub = "",
      grid = "",
      note = "";

    if (kind === "customer") {
      detailTitle.innerHTML =
        '<i class="bi bi-person-fill"></i> Customer details';
      heroIcon = "bi-person-fill";
      heroTitle = record.firstName + " " + record.lastName;
      heroSub = record.email + " · " + record.country;
      grid =
        detailItem("Customer ID", record.id.toUpperCase()) +
        detailItem("First name", record.firstName) +
        detailItem("Last name", record.lastName) +
        detailItem("Email", record.email) +
        detailItem("Phone", record.phone) +
        detailItem("Country", record.country) +
        detailItem("City", record.city || "—") +
        detailItem("Membership", record.tier || "—") +
        detailItem("Joined", formatDate(record.joined)) +
        detailItem("Total bookings", String(record.bookings)) +
        detailItem("Account status", record.status);
    }
    if (kind === "flight") {
      detailTitle.innerHTML =
        '<i class="bi bi-airplane-fill"></i> Flight booking details';
      heroIcon = "bi-airplane-fill";
      heroTitle = record.from + " → " + record.to;
      heroSub = record.airline + " " + record.flightNo + " · " + record.ref;
      grid =
        detailItem("Reference", record.ref) +
        detailItem("Customer", customerName(record.customerId)) +
        detailItem("Airline", record.airline) +
        detailItem("Flight number", record.flightNo) +
        detailItem("Cabin", record.cabin) +
        detailItem("Departure date", formatDate(record.date)) +
        detailItem("Departure time", record.time) +
        detailItem("Amount", formatMoney(record.amount)) +
        detailItem("Status", record.status);
    }
    if (kind === "hotel") {
      detailTitle.innerHTML =
        '<i class="bi bi-building-fill"></i> Hotel reservation details';
      heroIcon = "bi-building-fill";
      heroTitle = record.property;
      heroSub = record.location + " · " + record.ref;
      grid =
        detailItem("Reference", record.ref) +
        detailItem("Customer", customerName(record.customerId)) +
        detailItem("Property", record.property) +
        detailItem("Location", record.location) +
        detailItem("Room type", record.roomType) +
        detailItem("Check-in", formatDate(record.checkIn)) +
        detailItem("Check-out", formatDate(record.checkOut)) +
        detailItem("Nights", record.nights + " nights") +
        detailItem("Guests", record.guests) +
        detailItem("Amount", formatMoney(record.amount)) +
        detailItem("Status", record.status);
    }
    if (kind === "loan") {
      detailTitle.innerHTML =
        '<i class="bi bi-cash-coin"></i> Financing enquiry details';
      heroIcon = "bi-cash-coin";
      heroTitle = formatMoney(record.amount) + " over " + record.period;
      heroSub = record.ref + " · " + record.purpose;
      grid =
        detailItem("Reference", record.ref) +
        detailItem("Customer", customerName(record.customerId)) +
        detailItem("Amount requested", formatMoney(record.amount)) +
        detailItem("Repayment period", record.period) +
        detailItem("Purpose of travel", record.purpose) +
        detailItem("Submitted", formatDate(record.submitted)) +
        detailItem("Note", record.note || "—") +
        detailItem("Workflow status", record.status);
      note =
        '<p class="modal-note mt-3"><i class="bi bi-shield-exclamation"></i>Travelbookings is not a lender. This status reflects the enquiry workflow only and is not an approval or decline. All financing decisions belong to the independent licensed lending partner.</p>';
    }
    if (kind === "transaction") {
      detailTitle.innerHTML =
        '<i class="bi bi-credit-card-2-front-fill"></i> Transaction details';
      heroIcon = "bi-credit-card-2-front-fill";
      heroTitle = formatMoney(record.amount);
      heroSub = record.ref + " · related to " + record.related;
      grid =
        detailItem("Reference", record.ref) +
        detailItem("Customer", customerName(record.customerId)) +
        detailItem("Related booking", record.related) +
        detailItem("Payment method", record.method) +
        detailItem("Date", formatDate(record.date)) +
        detailItem("Amount", formatMoney(record.amount)) +
        detailItem("Status", record.status);
    }
    if (kind === "destination") {
      detailTitle.innerHTML =
        '<i class="bi bi-globe-americas"></i> Destination details';
      heroIcon = "bi-globe-americas";
      heroTitle = record.city + ", " + record.country;
      heroSub = record.continent + " · " + record.status;
      grid =
        detailItem("City", record.city) +
        detailItem("Country", record.country) +
        detailItem("Continent", record.continent) +
        detailItem("Travel styles", (record.categories || []).join(", ")) +
        detailItem("Status", record.status) +
        detailItem("Short description", record.blurb || "—");
    }

    if (!note) {
      note =
        '<p class="modal-note mt-3"><i class="bi bi-info-circle"></i>Live record synchronized in real time with the CJ Travels MySQL database.</p>';
    }

    detailBody.innerHTML =
      '<div class="detail-hero">' +
      '<span class="icon-box"><i class="bi ' +
      heroIcon +
      '"></i></span>' +
      '<span class="hero-text"><strong>' +
      escapeHTML(heroTitle) +
      "</strong><span>" +
      escapeHTML(heroSub) +
      "</span></span>" +
      (record.status ? statusPill(record.status) : "") +
      "</div>" +
      '<div class="detail-grid">' +
      grid +
      "</div>" +
      note;

    bootstrap.Modal.getOrCreateInstance(detailModalEl).show();
  }

  var editModalEl = $("editModal");
  var editFields = $("editFields");
  var editTitle = $("editModalTitle");
  var activeEdit = { kind: null, id: null };

  function customerSelectHTML(id, value) {
    var options = (DATA.customers || [])
      .map(function (customer) {
        var label = (customer.firstName + " " + customer.lastName).trim();
        return (
          '<option value="' +
          escapeHTML(customer.id) +
          '"' +
          (customer.id === value ? " selected" : "") +
          ">" +
          escapeHTML(label) +
          "</option>"
        );
      })
      .join("");
    return (
      '<div class="col-md-6"><label class="form-label" for="' +
      id +
      '">Customer</label><select class="form-select" id="' +
      id +
      '">' +
      options +
      "</select></div>"
    );
  }

  function fieldHTML(id, label, value, type) {
    type = type || "text";
    return (
      '<div class="col-md-6"><label class="form-label" for="' +
      id +
      '">' +
      escapeHTML(label) +
      '</label><input type="' +
      type +
      '" class="form-control" id="' +
      id +
      '" value="' +
      escapeHTML(value == null ? "" : value) +
      '" /></div>'
    );
  }

  function selectHTML(id, label, value, options) {
    return (
      '<div class="col-md-6"><label class="form-label" for="' +
      id +
      '">' +
      escapeHTML(label) +
      '</label><select class="form-select" id="' +
      id +
      '">' +
      options
        .map(function (opt) {
          return (
            '<option value="' +
            escapeHTML(opt) +
            '"' +
            (opt === value ? " selected" : "") +
            ">" +
            escapeHTML(opt) +
            "</option>"
          );
        })
        .join("") +
      "</select></div>"
    );
  }

  function openEdit(kind, id) {
    if (!editModalEl || !editFields || !editTitle) return;
    var record = findRecord(kind, id);
    if (!record) return;
    activeEdit = { kind: kind, id: id };

    if (kind === "customer") {
      editTitle.innerHTML = '<i class="bi bi-person-fill"></i> Edit customer';
      editFields.innerHTML =
        fieldHTML("editFirst", "First name", record.firstName) +
        fieldHTML("editLast", "Last name", record.lastName) +
        fieldHTML("editEmail", "Email", record.email, "email") +
        fieldHTML("editPhone", "Phone", record.phone === "—" ? "" : record.phone) +
        fieldHTML("editCountry", "Country", record.country) +
        fieldHTML("editCity", "City", record.city || "") +
        selectHTML("editTier", "Membership", record.tier || "Gold member", [
          "Standard member",
          "Silver member",
          "Gold member",
          "Platinum member",
        ]) +
        selectHTML("editStatus", "Status", record.status, [
          "Active",
          "Inactive",
        ]);
    }
    if (kind === "flight") {
      editTitle.innerHTML =
        '<i class="bi bi-airplane-fill"></i> Edit flight booking';
      editFields.innerHTML =
        fieldHTML("editAirline", "Airline", record.airline) +
        fieldHTML("editFlightNo", "Flight number", record.flightNo) +
        fieldHTML("editFrom", "From", record.from) +
        fieldHTML("editTo", "To", record.to) +
        fieldHTML("editDate", "Departure date", record.date, "date") +
        fieldHTML("editTime", "Departure time", record.time || "", "time") +
        fieldHTML("editArrival", "Arrival time", record.arrival || "", "time") +
        fieldHTML("editDuration", "Duration", record.duration || "") +
        fieldHTML("editStops", "Stops", record.stops || "Direct") +
        fieldHTML("editCabin", "Cabin", record.cabin || "Economy") +
        fieldHTML("editAmount", "Amount (₦)", record.amount, "number") +
        customerSelectHTML("editFlightCustomer", record.customerId) +
        selectHTML("editStatus", "Status", record.status, [
          "Confirmed",
          "Pending",
          "Completed",
          "Cancelled",
        ]);
    }
    if (kind === "hotel") {
      editTitle.innerHTML =
        '<i class="bi bi-building-fill"></i> Edit hotel reservation';
      editFields.innerHTML =
        fieldHTML("editProperty", "Property", record.property) +
        fieldHTML("editLocation", "Location", record.location) +
        fieldHTML("editRoomType", "Room type", record.roomType || "") +
        fieldHTML("editGuests", "Guests", record.guests || "") +
        fieldHTML("editCheckIn", "Check-in date", record.checkIn, "date") +
        fieldHTML("editCheckOut", "Check-out date", record.checkOut || "", "date") +
        fieldHTML("editNights", "Nights", record.nights, "number") +
        fieldHTML("editAmount", "Total amount (₦)", record.amount, "number") +
        customerSelectHTML("editHotelCustomer", record.customerId) +
        selectHTML("editStatus", "Status", record.status, [
          "Confirmed",
          "Pending",
          "Completed",
          "Cancelled",
        ]);
    }
    if (kind === "loan") {
      editTitle.innerHTML = '<i class="bi bi-cash-coin"></i> Edit application';
      editFields.innerHTML =
        fieldHTML(
          "editAmount",
          "Amount requested (₦)",
          record.amount,
          "number",
        ) +
        fieldHTML("editPeriod", "Repayment period", record.period) +
        fieldHTML("editPurpose", "Purpose of travel", record.purpose) +
        fieldHTML("editNote", "Note", record.note || "") +
        customerSelectHTML("editLoanCustomer", record.customerId) +
        selectHTML("editStatus", "Workflow status", record.status, [
          "In review",
          "Awaiting documents",
          "Referred to partner",
          "Draft",
          "Approved",
        ]);
    }
    if (kind === "transaction") {
      editTitle.innerHTML =
        '<i class="bi bi-credit-card-2-front-fill"></i> Edit transaction';
      editFields.innerHTML =
        customerSelectHTML("editTxnCustomer", record.customerId) +
        fieldHTML("editRelated", "Related booking", record.related === "—" ? "" : record.related) +
        selectHTML("editMethod", "Payment method", record.method, (function () {
          var methods = ["Card", "Paystack / Card", "Bank Transfer", "Cash"];
          if (record.method && methods.indexOf(record.method) === -1) methods.unshift(record.method);
          return methods;
        })()) +
        fieldHTML("editDate", "Date", String(record.date || "").slice(0, 10), "date") +
        fieldHTML("editAmount", "Amount (₦)", record.amount, "number") +
        selectHTML("editStatus", "Status", record.status, [
          "Paid",
          "Pending",
          "Refunded",
          "Failed",
        ]);
    }
    if (kind === "destination") {
      editTitle.innerHTML =
        '<i class="bi bi-globe-americas"></i> Edit destination';
      editFields.innerHTML =
        fieldHTML("editCity", "City", record.city) +
        fieldHTML("editCountry", "Country", record.country) +
        selectHTML("editContinent", "Continent", record.continent, [
          "Africa",
          "Europe",
          "Asia",
          "Middle East",
          "Americas",
          "Oceania",
        ]) +
        selectHTML("editStatus", "Status", record.status, [
          "Active",
          "Inactive",
        ]) +
        '<div class="col-12"><label class="form-label" for="editCategories">Travel styles</label><input type="text" class="form-control" id="editCategories" value="' +
        escapeHTML((record.categories || []).join(", ")) +
        '" /></div>' +
        '<div class="col-12"><label class="form-label" for="editImage">Image URL</label><input type="url" class="form-control" id="editImage" value="' +
        escapeHTML(record.image || "") +
        '" /></div>' +
        '<div class="col-12"><label class="form-label" for="editBlurb">Short description</label><input type="text" class="form-control" id="editBlurb" value="' +
        escapeHTML(record.blurb || "") +
        '" /></div>';
    }

    bootstrap.Modal.getOrCreateInstance(editModalEl).show();
  }

  if ($("saveEditBtn")) {
    $("saveEditBtn").addEventListener("click", function () {
      var kind = activeEdit.kind;
      var record = findRecord(kind, activeEdit.id);
      if (!record) return;
      var statusEl = $("editStatus");

      if (kind === "customer") {
        record.firstName = $("editFirst").value.trim() || record.firstName;
        record.lastName = $("editLast").value.trim() || record.lastName;
        record.email = $("editEmail").value.trim() || record.email;
        record.phone = $("editPhone").value.trim() || record.phone;
        record.country = $("editCountry").value.trim() || record.country;
        if ($("editCity")) record.city = $("editCity").value.trim() || record.city;
        if ($("editTier")) record.tier = $("editTier").value;
        if (statusEl) record.status = statusEl.value;
      }
      if (kind === "flight") {
        record.airline = $("editAirline").value.trim() || record.airline;
        record.flightNo = $("editFlightNo").value.trim() || record.flightNo;
        record.from = $("editFrom").value.trim() || record.from;
        record.to = $("editTo").value.trim() || record.to;
        record.date = $("editDate").value || record.date;
        if ($("editTime")) record.time = $("editTime").value || record.time;
        if ($("editArrival")) record.arrival = $("editArrival").value || record.arrival;
        if ($("editDuration")) record.duration = $("editDuration").value.trim() || record.duration;
        if ($("editStops")) record.stops = $("editStops").value.trim() || record.stops;
        if ($("editCabin")) record.cabin = $("editCabin").value.trim() || record.cabin;
        if ($("editFlightCustomer")) record.customerId = $("editFlightCustomer").value;
        record.amount = Number($("editAmount").value) || record.amount;
        if (statusEl) record.status = statusEl.value;
      }
      if (kind === "hotel") {
        record.property = $("editProperty").value.trim() || record.property;
        record.location = $("editLocation").value.trim() || record.location;
        if ($("editRoomType")) record.roomType = $("editRoomType").value.trim() || record.roomType;
        if ($("editGuests")) record.guests = $("editGuests").value.trim() || record.guests;
        record.checkIn = $("editCheckIn").value || record.checkIn;
        if ($("editCheckOut")) record.checkOut = $("editCheckOut").value || record.checkOut;
        record.nights = Number($("editNights").value) || record.nights;
        if (record.checkIn && record.checkOut) {
          var stayNights = Math.round((new Date(record.checkOut + "T00:00:00") - new Date(record.checkIn + "T00:00:00")) / 86400000);
          if (stayNights > 0) record.nights = stayNights;
        }
        if ($("editHotelCustomer")) record.customerId = $("editHotelCustomer").value;
        record.amount = Number($("editAmount").value) || record.amount;
        if (statusEl) record.status = statusEl.value;
      }
      if (kind === "loan") {
        record.amount = Number($("editAmount").value) || record.amount;
        record.period = $("editPeriod").value.trim() || record.period;
        record.purpose = $("editPurpose").value.trim() || record.purpose;
        if ($("editNote")) record.note = $("editNote").value.trim();
        if ($("editLoanCustomer")) record.customerId = $("editLoanCustomer").value;
        if (statusEl) record.status = statusEl.value;
      }
      if (kind === "transaction") {
        if ($("editTxnCustomer")) record.customerId = $("editTxnCustomer").value;
        if ($("editRelated")) record.related = $("editRelated").value.trim() || "—";
        if ($("editMethod")) record.method = $("editMethod").value;
        if ($("editDate") && $("editDate").value) record.date = $("editDate").value;
        record.amount = Number($("editAmount").value) || record.amount;
        if (statusEl) record.status = statusEl.value;
      }
      if (kind === "destination") {
        record.city = $("editCity").value.trim() || record.city;
        record.country = $("editCountry").value.trim() || record.country;
        if ($("editContinent")) record.continent = $("editContinent").value;
        if (statusEl) record.status = statusEl.value;
        if ($("editCategories")) {
          record.categories = $("editCategories").value.split(",").map(function (item) {
            return item.trim();
          }).filter(Boolean);
          if (!record.categories.length) record.categories = ["City Breaks"];
        }
        if ($("editImage")) record.image = $("editImage").value.trim();
        record.blurb = $("editBlurb").value.trim();
      }

      saveData();
      bootstrap.Modal.getInstance(editModalEl).hide();
      refreshAfterChange();

      // Real-time synchronization to MySQL
      var updateUrl = (window.CJ_API_URLS && window.CJ_API_URLS.updateRecord) || "/admin/api/records/update";
      var token = window.CJ_CSRF_TOKEN || "";
      var fieldsToSync = {};
      if (kind === "customer") {
        fieldsToSync = {
          firstName: record.firstName,
          lastName: record.lastName,
          email: record.email,
          phone: record.phone,
          country: record.country,
          city: record.city,
          tier: record.tier,
          status: record.status
        };
      } else if (kind === "flight") {
        fieldsToSync = {
          airline: record.airline,
          flightNo: record.flightNo,
          from: record.from,
          to: record.to,
          date: record.date,
          time: record.time,
          arrival: record.arrival,
          duration: record.duration,
          stops: record.stops,
          cabin: record.cabin,
          customerId: record.customerId,
          amount: record.amount,
          status: record.status
        };
      } else if (kind === "hotel") {
        fieldsToSync = {
          property: record.property,
          location: record.location,
          roomType: record.roomType,
          guests: record.guests,
          checkIn: record.checkIn,
          checkOut: record.checkOut,
          nights: record.nights,
          customerId: record.customerId,
          amount: record.amount,
          status: record.status
        };
      } else if (kind === "loan") {
        fieldsToSync = {
          amount: record.amount,
          period: record.period,
          purpose: record.purpose,
          note: record.note,
          customerId: record.customerId,
          status: record.status
        };
      } else if (kind === "transaction") {
        fieldsToSync = {
          customerId: record.customerId,
          related: record.related === "—" ? "" : record.related,
          method: record.method,
          date: String(record.date || "").slice(0, 10),
          amount: record.amount,
          status: record.status
        };
      } else if (kind === "destination") {
        fieldsToSync = {
          city: record.city,
          country: record.country,
          continent: record.continent,
          categories: record.categories,
          image: record.image,
          status: record.status,
          blurb: record.blurb
        };
      }

      fetch(updateUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": token,
          "X-Requested-With": "XMLHttpRequest"
        },
        body: JSON.stringify({
          _token: token,
          kind: kind,
          id: activeEdit.id,
          fields: fieldsToSync
        })
      })
      .then(function(res) { return res.json(); })
      .then(function(res) {
        if (res && res.data) {
          DATA = res.data;
          refreshAfterChange();
        }
        if (!res || !res.success) {
          window.alert((res && (res.error || res.message)) || "Could not save this record.");
        }
      })
      .catch(function(err) {
        console.warn("MySQL sync update error:", err);
      });
    });
  }

  var deleteModalEl = $("deleteModal");
  var deleteLabel = $("deleteRecordLabel");
  var pendingDelete = { kind: null, id: null };

  function openDelete(kind, id) {
    if (!deleteModalEl || !deleteLabel) return;
    var record = findRecord(kind, id);
    if (!record) return;
    pendingDelete = { kind: kind, id: id };
    var label = "this record";
    if (kind === "customer") label = record.firstName + " " + record.lastName;
    if (kind === "flight")
      label = record.ref + " · " + record.from + " → " + record.to;
    if (kind === "hotel") label = record.ref + " · " + record.property;
    if (kind === "loan")
      label = record.ref + " · " + formatMoney(record.amount);
    if (kind === "transaction")
      label = record.ref + " · " + formatMoney(record.amount);
    if (kind === "destination") label = record.city + ", " + record.country;
    deleteLabel.textContent = label;
    bootstrap.Modal.getOrCreateInstance(deleteModalEl).show();
  }

  if ($("confirmDeleteBtn")) {
    $("confirmDeleteBtn").addEventListener("click", function () {
      var kind = pendingDelete.kind,
        id = pendingDelete.id;
      if (kind === "customer")
        DATA.customers = DATA.customers.filter(function (c) {
          return c.id !== id;
        });
      if (kind === "flight")
        DATA.flights = DATA.flights.filter(function (f) {
          return f.id !== id;
        });
      if (kind === "hotel")
        DATA.hotels = DATA.hotels.filter(function (h) {
          return h.id !== id;
        });
      if (kind === "loan")
        DATA.loans = DATA.loans.filter(function (l) {
          return l.id !== id;
        });
      if (kind === "transaction")
        DATA.transactions = DATA.transactions.filter(function (t) {
          return t.id !== id;
        });
      if (kind === "destination")
        DATA.destinations = DATA.destinations.filter(function (d) {
          return d.id !== id;
        });
      saveData();
      bootstrap.Modal.getInstance(deleteModalEl).hide();
      refreshAfterChange();

      // Real-time delete from MySQL
      var deleteUrl = (window.CJ_API_URLS && window.CJ_API_URLS.deleteRecord) || "/admin/api/records/delete";
      var token = window.CJ_CSRF_TOKEN || "";

      fetch(deleteUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": token,
          "X-Requested-With": "XMLHttpRequest"
        },
        body: JSON.stringify({
          _token: token,
          kind: kind,
          id: id
        })
      })
      .then(function(res) { return res.json(); })
      .then(function(res) {
        if (res && res.success && res.data) {
          DATA = res.data;
          refreshAfterChange();
        }
      })
      .catch(function(err) {
        console.warn("MySQL sync delete error:", err);
      });
    });
  }

  document.addEventListener("click", function (event) {
    var pagerBtn = event.target.closest("[data-txn-page]");
    if (pagerBtn && !pagerBtn.disabled) {
      var targetPage = pagerBtn.getAttribute("data-txn-page");
      if (targetPage === "prev") txnState.page -= 1;
      else if (targetPage === "next") txnState.page += 1;
      else txnState.page = Number(targetPage) || 1;
      renderTransactions();
      return;
    }
    var viewBtn = event.target.closest(".js-view");
    if (viewBtn) {
      openDetail(viewBtn.dataset.kind, viewBtn.dataset.id);
      return;
    }
    var editBtn = event.target.closest(".js-edit");
    if (editBtn) {
      openEdit(editBtn.dataset.kind, editBtn.dataset.id);
      return;
    }
    var deleteBtn = event.target.closest(".js-delete");
    if (deleteBtn) {
      openDelete(deleteBtn.dataset.kind, deleteBtn.dataset.id);
      return;
    }
    var navTrigger = event.target.closest("[data-nav]");
    if (navTrigger) {
      var navName = navTrigger.dataset.nav;
      if (navName === "logout") {
        var logoutModal = $("logoutModal");
        if (logoutModal)
          bootstrap.Modal.getOrCreateInstance(logoutModal).show();
      } else if (navName) {
        window.location.href = navName + "";
      }
    }
  });

  /* =========================================================
     PAGE 1: DASHBOARD (Overview)
     ========================================================= */
  function renderDashboardStats() {
    var counts = refreshCounts();
    var activeCustomers = DATA.customers.filter(function (c) {
      return c.status === "Active";
    }).length;
    var pendingFlights = DATA.flights.filter(function (f) {
      return f.status === "Pending";
    }).length;
    var pendingHotels = DATA.hotels.filter(function (h) {
      return h.status === "Pending";
    }).length;
    if ($("statCustomers"))
      $("statCustomers").textContent = String(counts.customers);
    if ($("statActiveCustomers"))
      $("statActiveCustomers").textContent = String(activeCustomers);
    if ($("statFlights")) $("statFlights").textContent = String(counts.flights);
    if ($("statHotels")) $("statHotels").textContent = String(counts.hotels);
    if ($("statLoans")) $("statLoans").textContent = String(counts.loans);
    if ($("statPendingFlights"))
      $("statPendingFlights").textContent = String(pendingFlights);
    if ($("statPendingHotels"))
      $("statPendingHotels").textContent = String(pendingHotels);
  }

  function renderBookingChart(containerId, labelsId) {
    var host = $(containerId);
    var labelsHost = $(labelsId);
    if (!host || !labelsHost) return;

    var max = 1;
    DATA.monthly.forEach(function (m) {
      max = Math.max(max, m.flights, m.hotels);
    });

    host.innerHTML = DATA.monthly
      .map(function (m) {
        var fh = Math.max(3, Math.round((m.flights / max) * 100));
        var hh = Math.max(3, Math.round((m.hotels / max) * 100));
        return (
          '<div class="bar-group">' +
          '<div class="bar flights" style="height:' +
          fh +
          '%;" data-tip="' +
          escapeHTML(m.month) +
          " · " +
          m.flights +
          ' flights"></div>' +
          '<div class="bar hotels" style="height:' +
          hh +
          '%;" data-tip="' +
          escapeHTML(m.month) +
          " · " +
          m.hotels +
          ' hotels"></div>' +
          "</div>"
        );
      })
      .join("");

    labelsHost.innerHTML = DATA.monthly
      .map(function (m) {
        return "<span>" + escapeHTML(m.month) + "</span>";
      })
      .join("");
  }

  function renderStatusDonut() {
    var donut = $("statusDonut");
    var legend = $("statusLegend");
    var totalEl = $("statusDonutTotal");
    if (!donut || !legend) return;

    var all = DATA.flights.concat(DATA.hotels);
    var counts = { Confirmed: 0, Pending: 0, Completed: 0, Cancelled: 0 };
    all.forEach(function (b) {
      if (counts[b.status] === undefined) counts[b.status] = 0;
      counts[b.status]++;
    });

    var colors = {
      Confirmed: "#2e7d5b",
      Pending: "#a8842c",
      Completed: "#8a8378",
      Cancelled: "#a3452e",
    };
    var total = all.length;
    if (totalEl) totalEl.textContent = String(total);

    if (!total) {
      donut.style.background = "var(--hairline-soft)";
      legend.innerHTML =
        "<p style=\"font-family:'DM Sans',sans-serif;font-size:12px;color:var(--muted);margin:0;\">No bookings yet.</p>";
      return;
    }

    var stops = [],
      cursor = 0,
      keys = ["Confirmed", "Pending", "Completed", "Cancelled"];
    keys.forEach(function (key) {
      var value = counts[key] || 0;
      if (!value) return;
      var start = (cursor / total) * 360;
      cursor += value;
      var end = (cursor / total) * 360;
      stops.push(colors[key] + " " + start + "deg " + end + "deg");
    });

    donut.style.background = "conic-gradient(" + stops.join(", ") + ")";
    legend.innerHTML = keys
      .map(function (key) {
        var value = counts[key] || 0;
        return (
          '<div class="donut-legend-item">' +
          '<span class="dot" style="background:' +
          colors[key] +
          ';"></span>' +
          "<span>" +
          key +
          "</span>" +
          '<span class="count">' +
          value +
          "</span>" +
          "</div>"
        );
      })
      .join("");
  }

  function renderTxnOverview() {
    var total = 0,
      paid = 0,
      pending = 0;
    var statuses = { Paid: 0, Pending: 0, Refunded: 0, Failed: 0 };
    DATA.transactions.forEach(function (t) {
      total += t.amount;
      if (t.status === "Paid") paid += t.amount;
      if (t.status === "Pending") pending += t.amount;
      statuses[t.status] = (statuses[t.status] || 0) + t.amount;
    });
    if ($("txnTotal")) $("txnTotal").textContent = shortMoney(total);
    if ($("txnPaid")) $("txnPaid").textContent = shortMoney(paid);
    if ($("txnPending")) $("txnPending").textContent = shortMoney(pending);

    var chart = $("txnChart");
    if (!chart) return;

    var max = Math.max(
      1,
      statuses.Paid,
      statuses.Pending,
      statuses.Refunded,
      statuses.Failed,
    );
    var groups = [
      { key: "Paid", value: statuses.Paid, color: "var(--green)" },
      { key: "Pending", value: statuses.Pending, color: "var(--gold)" },
      { key: "Refunded", value: statuses.Refunded, color: "var(--navy-soft)" },
      { key: "Failed", value: statuses.Failed, color: "var(--error)" },
    ];

    chart.innerHTML = groups
      .map(function (g) {
        var h = Math.max(3, Math.round((g.value / max) * 100));
        return (
          '<div class="bar-group"><div class="bar" style="height:' +
          h +
          "%;background:" +
          g.color +
          ';" data-tip="' +
          g.key +
          " · " +
          formatMoney(g.value) +
          '"></div></div>'
        );
      })
      .join("");
  }

  function renderActivity() {
    var host = $("activityList");
    if (!host) return;
    if (!DATA.activities || !DATA.activities.length) {
      host.innerHTML = '<li class="text-muted p-3">No activity recorded yet.</li>';
      return;
    }
    host.innerHTML = DATA.activities
      .map(function (item) {
        var tone = item.tone ? " " + item.tone : "";
        return (
          '<li class="activity-item">' +
          '<span class="activity-icon' +
          tone +
          '"><i class="bi ' +
          item.icon +
          '"></i></span>' +
          '<div class="activity-body"><strong>' +
          escapeHTML(item.title) +
          "</strong><span>" +
          escapeHTML(item.text) +
          "</span></div>" +
          '<span class="activity-time">' +
          escapeHTML(item.time) +
          "</span>" +
          "</li>"
        );
      })
      .join("");
  }

  function renderRecentBookings() {
    var host = $("recentBookingsBody");
    if (!host) return;

    var rows = [];
    DATA.flights.forEach(function (f) {
      rows.push({
        kind: "flight",
        id: f.id,
        ref: f.ref,
        customer: customerName(f.customerId),
        label: f.airline + " · " + f.from + " → " + f.to,
        date: f.date,
        amount: f.amount,
        status: f.status,
      });
    });
    DATA.hotels.forEach(function (h) {
      rows.push({
        kind: "hotel",
        id: h.id,
        ref: h.ref,
        customer: customerName(h.customerId),
        label: h.property + " · " + h.location,
        date: h.checkIn,
        amount: h.amount,
        status: h.status,
      });
    });
    rows.sort(function (a, b) {
      return a.date < b.date ? 1 : -1;
    });
    rows = rows.slice(0, 6);

    if (!rows.length) {
      host.innerHTML = emptyRow(7, "No bookings to show.");
      return;
    }

    host.innerHTML = rows
      .map(function (row) {
        var icon =
          row.kind === "flight" ? "bi-airplane-fill" : "bi-building-fill";
        return (
          "<tr>" +
          '<td data-label="Reference"><span class="cell-mono">' +
          escapeHTML(row.ref) +
          "</span></td>" +
          '<td data-label="Customer"><span class="cell-strong">' +
          escapeHTML(row.customer) +
          "</span></td>" +
          '<td data-label="Booking"><span class="cell-strong"><i class="bi ' +
          icon +
          '" style="color:var(--gold);font-size:11px;margin-right:6px;"></i>' +
          escapeHTML(row.label) +
          "</span></td>" +
          '<td data-label="Travel date"><span class="cell-strong">' +
          escapeHTML(formatDate(row.date)) +
          "</span></td>" +
          '<td data-label="Amount"><span class="cell-mono">' +
          escapeHTML(formatMoney(row.amount)) +
          "</span></td>" +
          '<td data-label="Status">' +
          statusPill(row.status) +
          "</td>" +
          '<td class="text-end"><button type="button" class="btn btn-ghost btn-xs js-view" data-kind="' +
          row.kind +
          '" data-id="' +
          escapeHTML(row.id) +
          '">View</button></td>' +
          "</tr>"
        );
      })
      .join("");
  }

  /* =========================================================
     PAGE 2: CUSTOMERS
     ========================================================= */
  var customerState = { query: "", status: "" };

  function renderCustomers() {
    var host = $("customersBody");
    if (!host) return;
    var q = customerState.query.toLowerCase().trim();
    var filtered = DATA.customers.filter(function (c) {
      if (customerState.status && c.status !== customerState.status)
        return false;
      if (!q) return true;
      var hay = (
        c.firstName +
        " " +
        c.lastName +
        " " +
        c.email +
        " " +
        c.country +
        " " +
        (c.city || "") +
        " " +
        c.phone
      ).toLowerCase();
      return hay.indexOf(q) !== -1;
    });
    if ($("customerCount"))
      $("customerCount").textContent = String(filtered.length);
    if (!filtered.length) {
      host.innerHTML = emptyRow(7, "No customers match this search.");
      return;
    }

    host.innerHTML = filtered
      .map(function (c) {
        return (
          "<tr>" +
          '<td data-label="Customer"><div class="cell-person"><span class="avatar">' +
          escapeHTML(initials(c.firstName, c.lastName)) +
          '</span><div><span class="cell-strong">' +
          escapeHTML(c.firstName + " " + c.lastName) +
          '</span><span class="cell-sub">' +
          escapeHTML(c.id.toUpperCase()) +
          "</span></div></div></td>" +
          '<td data-label="Contact"><span class="cell-strong">' +
          escapeHTML(c.email) +
          '</span><span class="cell-sub">' +
          escapeHTML(c.phone) +
          "</span></td>" +
          '<td data-label="Country"><span class="cell-strong">' +
          escapeHTML(c.country) +
          '</span><span class="cell-sub">' +
          escapeHTML(c.city || "") +
          "</span></td>" +
          '<td data-label="Bookings"><span class="cell-mono">' +
          c.bookings +
          "</span></td>" +
          '<td data-label="Joined"><span class="cell-strong">' +
          escapeHTML(formatDate(c.joined)) +
          "</span></td>" +
          '<td data-label="Status">' +
          statusPill(c.status) +
          "</td>" +
          '<td class="text-end"><div class="row-actions">' +
          '<button type="button" class="btn btn-ghost btn-icon js-view" data-kind="customer" data-id="' +
          escapeHTML(c.id) +
          '" aria-label="View customer"><i class="bi bi-eye"></i></button>' +
          '<button type="button" class="btn btn-soft btn-icon js-edit" data-kind="customer" data-id="' +
          escapeHTML(c.id) +
          '" aria-label="Edit customer"><i class="bi bi-pencil"></i></button>' +
          '<button type="button" class="btn btn-danger-soft btn-icon js-delete" data-kind="customer" data-id="' +
          escapeHTML(c.id) +
          '" aria-label="Delete customer"><i class="bi bi-trash3"></i></button>' +
          "</div></td>" +
          "</tr>"
        );
      })
      .join("");
  }

  /* =========================================================
     PAGE 3: FLIGHT BOOKINGS
     ========================================================= */
  var flightState = { query: "", status: "" };

  function renderFlights() {
    var host = $("flightsBody");
    if (!host) return;
    var q = flightState.query.toLowerCase().trim();
    var filtered = DATA.flights.filter(function (f) {
      if (flightState.status && f.status !== flightState.status) return false;
      if (!q) return true;
      var hay = (
        f.ref +
        " " +
        customerName(f.customerId) +
        " " +
        f.airline +
        " " +
        f.flightNo +
        " " +
        f.from +
        " " +
        f.to
      ).toLowerCase();
      return hay.indexOf(q) !== -1;
    });
    if ($("flightCount"))
      $("flightCount").textContent = String(filtered.length);
    if (!filtered.length) {
      host.innerHTML = emptyRow(7, "No flight bookings match this search.");
      return;
    }

    host.innerHTML = filtered
      .map(function (f) {
        return (
          "<tr>" +
          '<td data-label="Reference"><span class="cell-mono">' +
          escapeHTML(f.ref) +
          "</span></td>" +
          '<td data-label="Customer"><span class="cell-strong">' +
          escapeHTML(customerName(f.customerId)) +
          "</span></td>" +
          '<td data-label="Airline & route"><span class="cell-strong">' +
          escapeHTML(f.airline + " " + f.flightNo) +
          '</span><span class="cell-sub">' +
          escapeHTML(f.from + " → " + f.to) +
          " · " +
          escapeHTML(f.cabin) +
          "</span></td>" +
          '<td data-label="Departure"><span class="cell-strong">' +
          escapeHTML(formatDate(f.date)) +
          '</span><span class="cell-sub">' +
          escapeHTML(f.time) +
          "</span></td>" +
          '<td data-label="Amount"><span class="cell-mono">' +
          escapeHTML(formatMoney(f.amount)) +
          "</span></td>" +
          '<td data-label="Status">' +
          statusPill(f.status) +
          "</td>" +
          '<td class="text-end"><div class="row-actions">' +
          '<button type="button" class="btn btn-ghost btn-icon js-view" data-kind="flight" data-id="' +
          escapeHTML(f.id) +
          '" aria-label="View booking"><i class="bi bi-eye"></i></button>' +
          '<button type="button" class="btn btn-soft btn-icon js-edit" data-kind="flight" data-id="' +
          escapeHTML(f.id) +
          '" aria-label="Edit booking"><i class="bi bi-pencil"></i></button>' +
          '<button type="button" class="btn btn-danger-soft btn-icon js-delete" data-kind="flight" data-id="' +
          escapeHTML(f.id) +
          '" aria-label="Delete booking"><i class="bi bi-trash3"></i></button>' +
          "</div></td>" +
          "</tr>"
        );
      })
      .join("");
  }

  /* =========================================================
     PAGE 4: HOTEL RESERVATIONS
     ========================================================= */
  var hotelState = { query: "", status: "" };

  function renderHotels() {
    var host = $("hotelsBody");
    if (!host) return;
    var q = hotelState.query.toLowerCase().trim();
    var filtered = DATA.hotels.filter(function (h) {
      if (hotelState.status && h.status !== hotelState.status) return false;
      if (!q) return true;
      var hay = (
        h.ref +
        " " +
        customerName(h.customerId) +
        " " +
        h.property +
        " " +
        h.location +
        " " +
        h.roomType
      ).toLowerCase();
      return hay.indexOf(q) !== -1;
    });
    if ($("hotelCount")) $("hotelCount").textContent = String(filtered.length);
    if (!filtered.length) {
      host.innerHTML = emptyRow(7, "No hotel reservations match this search.");
      return;
    }

    host.innerHTML = filtered
      .map(function (h) {
        return (
          "<tr>" +
          '<td data-label="Reference"><span class="cell-mono">' +
          escapeHTML(h.ref) +
          "</span></td>" +
          '<td data-label="Customer"><span class="cell-strong">' +
          escapeHTML(customerName(h.customerId)) +
          "</span></td>" +
          '<td data-label="Property"><span class="cell-strong">' +
          escapeHTML(h.property) +
          '</span><span class="cell-sub">' +
          escapeHTML(h.roomType) +
          "</span></td>" +
          '<td data-label="Stay"><span class="cell-strong">' +
          escapeHTML(formatDate(h.checkIn)) +
          '</span><span class="cell-sub">' +
          h.nights +
          " nights · " +
          escapeHTML(h.guests) +
          "</span></td>" +
          '<td data-label="Amount"><span class="cell-mono">' +
          escapeHTML(formatMoney(h.amount)) +
          "</span></td>" +
          '<td data-label="Status">' +
          statusPill(h.status) +
          "</td>" +
          '<td class="text-end"><div class="row-actions">' +
          '<button type="button" class="btn btn-ghost btn-icon js-view" data-kind="hotel" data-id="' +
          escapeHTML(h.id) +
          '" aria-label="View reservation"><i class="bi bi-eye"></i></button>' +
          '<button type="button" class="btn btn-soft btn-icon js-edit" data-kind="hotel" data-id="' +
          escapeHTML(h.id) +
          '" aria-label="Edit reservation"><i class="bi bi-pencil"></i></button>' +
          '<button type="button" class="btn btn-danger-soft btn-icon js-delete" data-kind="hotel" data-id="' +
          escapeHTML(h.id) +
          '" aria-label="Delete reservation"><i class="bi bi-trash3"></i></button>' +
          "</div></td>" +
          "</tr>"
        );
      })
      .join("");
  }

  /* =========================================================
     PAGE 5: TRAVEL LOAN APPLICATIONS
     ========================================================= */
  var loanState = { query: "", status: "" };

  function renderLoans() {
    var host = $("loansBody");
    if (!host) return;
    var q = loanState.query.toLowerCase().trim();
    var filtered = DATA.loans.filter(function (l) {
      if (loanState.status && l.status !== loanState.status) return false;
      if (!q) return true;
      var hay = (
        l.ref +
        " " +
        customerName(l.customerId) +
        " " +
        l.purpose +
        " " +
        l.status
      ).toLowerCase();
      return hay.indexOf(q) !== -1;
    });
    if ($("loanCount")) $("loanCount").textContent = String(filtered.length);
    if (!filtered.length) {
      host.innerHTML = emptyRow(8, "No applications match this search.");
      return;
    }

    host.innerHTML = filtered
      .map(function (l) {
        return (
          "<tr>" +
          '<td data-label="Reference"><span class="cell-mono">' +
          escapeHTML(l.ref) +
          "</span></td>" +
          '<td data-label="Customer"><span class="cell-strong">' +
          escapeHTML(customerName(l.customerId)) +
          "</span></td>" +
          '<td data-label="Amount"><span class="cell-mono">' +
          escapeHTML(formatMoney(l.amount)) +
          "</span></td>" +
          '<td data-label="Period"><span class="cell-strong">' +
          escapeHTML(l.period) +
          "</span></td>" +
          '<td data-label="Purpose"><span class="cell-strong">' +
          escapeHTML(l.purpose) +
          "</span></td>" +
          '<td data-label="Submitted"><span class="cell-strong">' +
          escapeHTML(formatDate(l.submitted)) +
          "</span></td>" +
          '<td data-label="Status">' +
          statusPill(l.status) +
          "</td>" +
          '<td class="text-end"><div class="row-actions">' +
          '<button type="button" class="btn btn-ghost btn-icon js-view" data-kind="loan" data-id="' +
          escapeHTML(l.id) +
          '" aria-label="View application"><i class="bi bi-eye"></i></button>' +
          '<button type="button" class="btn btn-soft btn-icon js-edit" data-kind="loan" data-id="' +
          escapeHTML(l.id) +
          '" aria-label="Edit application"><i class="bi bi-pencil"></i></button>' +
          '<button type="button" class="btn btn-danger-soft btn-icon js-delete" data-kind="loan" data-id="' +
          escapeHTML(l.id) +
          '" aria-label="Delete application"><i class="bi bi-trash3"></i></button>' +
          "</div></td>" +
          "</tr>"
        );
      })
      .join("");
  }

  /* =========================================================
     PAGE 6: DESTINATIONS
     ========================================================= */
  var destState = { query: "", continent: "" };

  function renderDestinations() {
    var host = $("destinationsGrid");
    if (!host) return;
    var q = destState.query.toLowerCase().trim();
    var filtered = DATA.destinations.filter(function (d) {
      if (destState.continent && d.continent !== destState.continent)
        return false;
      if (!q) return true;
      var hay = (
        d.city +
        " " +
        d.country +
        " " +
        d.continent +
        " " +
        (d.categories || []).join(" ")
      ).toLowerCase();
      return hay.indexOf(q) !== -1;
    });
    if ($("destinationCount"))
      $("destinationCount").textContent = String(filtered.length);
    if (!filtered.length) {
      host.innerHTML =
        '<div class="empty-row" style="grid-column:1/-1;"><i class="bi bi-globe-americas"></i>No destinations match this search.</div>';
      return;
    }

    host.innerHTML = filtered
      .map(function (d) {
        var tags = (d.categories || [])
          .map(function (cat) {
            return (
              '<span class="dest-admin-tag">' + escapeHTML(cat) + "</span>"
            );
          })
          .join("");
        var media = d.image
          ? '<img src="' +
            escapeHTML(d.image) +
            '" alt="' +
            escapeHTML(d.city) +
            '" loading="lazy" onerror="this.style.display=\'none\'" />'
          : "";
        return (
          '<article class="dest-admin-card">' +
          '<div class="dest-admin-media">' +
          media +
          "</div>" +
          '<div class="dest-admin-body">' +
          "<h4>" +
          escapeHTML(d.city) +
          "</h4>" +
          "<p>" +
          escapeHTML(d.country) +
          " · " +
          escapeHTML(d.continent) +
          "</p>" +
          '<div class="dest-admin-tags">' +
          tags +
          "</div>" +
          '<div class="d-flex justify-content-between align-items-center">' +
          statusPill(d.status) +
          '<div class="dest-admin-actions">' +
          '<button type="button" class="btn btn-ghost btn-icon js-view" data-kind="destination" data-id="' +
          escapeHTML(d.id) +
          '" aria-label="View destination"><i class="bi bi-eye"></i></button>' +
          '<button type="button" class="btn btn-soft btn-icon js-edit" data-kind="destination" data-id="' +
          escapeHTML(d.id) +
          '" aria-label="Edit destination"><i class="bi bi-pencil"></i></button>' +
          '<button type="button" class="btn btn-danger-soft btn-icon js-delete" data-kind="destination" data-id="' +
          escapeHTML(d.id) +
          '" aria-label="Delete destination"><i class="bi bi-trash3"></i></button>' +
          "</div>" +
          "</div>" +
          "</div>" +
          "</article>"
        );
      })
      .join("");
  }

  var destinationModalEl = $("destinationModal");
  var destinationForm = $("destinationForm");
  var destinationToast = $("destinationToast");

  function openAddDestination() {
    if (!destinationModalEl || !destinationForm) return;
    destinationForm.reset();
    if (destinationToast) {
      destinationToast.className = "toast-inline";
      destinationToast.innerHTML = "";
    }
    Array.prototype.forEach.call(
      destinationForm.querySelectorAll(".is-invalid"),
      function (el) {
        el.classList.remove("is-invalid");
      },
    );
    bootstrap.Modal.getOrCreateInstance(destinationModalEl).show();
  }

  if ($("addDestinationBtn"))
    $("addDestinationBtn").addEventListener("click", openAddDestination);
  if ($("addDestinationBtnTop"))
    $("addDestinationBtnTop").addEventListener("click", openAddDestination);

  if ($("saveDestinationBtn")) {
    $("saveDestinationBtn").addEventListener("click", function () {
      var cityEl = $("destCity"),
        countryEl = $("destCountry"),
        continentEl = $("destContinent");
      var valid = true;
      [cityEl, countryEl, continentEl].forEach(function (el) {
        if (!el || !el.value || !el.value.trim()) {
          if (el) el.classList.add("is-invalid");
          valid = false;
        } else el.classList.remove("is-invalid");
      });
      if (!valid) {
        if (destinationToast) {
          destinationToast.className = "toast-inline error show";
          destinationToast.innerHTML =
            '<i class="bi bi-exclamation-circle-fill"></i> Please fill in the required fields.';
        }
        return;
      }
      var categories = Array.prototype.slice
        .call($("destCategories").selectedOptions)
        .map(function (o) {
          return o.value;
        });
      if (!categories.length) categories = ["City Breaks"];
      var newDestItem = {
        id: "d" + Date.now(),
        city: cityEl.value.trim(),
        country: countryEl.value.trim(),
        continent: continentEl.value,
        categories: categories,
        blurb: $("destBlurb") ? $("destBlurb").value.trim() : "",
        image: $("destImage") ? $("destImage").value.trim() : "",
        status: "Active",
      };
      DATA.destinations.unshift(newDestItem);
      renderDestinations();
      refreshCounts();

      // Real-time create destination in MySQL
      var createDestUrl = (window.CJ_API_URLS && window.CJ_API_URLS.createDestination) || "/admin/api/destinations/create";
      var token = window.CJ_CSRF_TOKEN || "";

      fetch(createDestUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": token,
          "X-Requested-With": "XMLHttpRequest"
        },
        body: JSON.stringify({
          _token: token,
          city: newDestItem.city,
          country: newDestItem.country,
          continent: newDestItem.continent,
          categories: newDestItem.categories,
          blurb: newDestItem.blurb,
          image: newDestItem.image,
          status: "Active"
        })
      })
      .then(function(res) { return res.json(); })
      .then(function(res) {
        if (res && res.success && res.data) {
          DATA = res.data;
          renderDestinations();
          refreshCounts();
          if (destinationToast) {
            destinationToast.className = "toast-inline show";
            destinationToast.innerHTML =
              '<i class="bi bi-check-circle-fill"></i> ' +
              escapeHTML(res.message || "Destination added.");
          }
          window.setTimeout(function () {
            var modal = bootstrap.Modal.getInstance(destinationModalEl);
            if (modal) modal.hide();
          }, 600);
          return;
        }
        if (destinationToast) {
          destinationToast.className = "toast-inline error show";
          destinationToast.innerHTML =
            '<i class="bi bi-exclamation-circle-fill"></i> ' +
            escapeHTML((res && (res.error || res.message)) || "Could not add this destination.");
        }
      })
      .catch(function() {
        if (destinationToast) {
          destinationToast.className = "toast-inline error show";
          destinationToast.innerHTML =
            '<i class="bi bi-exclamation-circle-fill"></i> Could not reach the server.';
        }
      });
      if (destinationToast) {
        destinationToast.className = "toast-inline show";
        destinationToast.innerHTML =
          '<i class="bi bi-check-circle-fill"></i> Saving destination…';
      }
    });
  }

  ["destCity", "destCountry", "destContinent"].forEach(function (id) {
    var el = $(id);
    if (!el) return;
    el.addEventListener("input", function () {
      el.classList.remove("is-invalid");
    });
    el.addEventListener("change", function () {
      el.classList.remove("is-invalid");
    });
  });

  var customerModalEl = $("customerModal");
  var customerForm = $("customerForm");
  var customerToast = $("customerToast");

  function showCustomerToast(type, message) {
    if (!customerToast) return;
    customerToast.className = "toast-inline show" + (type === "error" ? " error" : "");
    customerToast.innerHTML =
      '<i class="bi ' +
      (type === "error" ? "bi-exclamation-circle-fill" : "bi-check-circle-fill") +
      '"></i> ' +
      escapeHTML(message);
  }

  function openAddCustomer() {
    if (!customerModalEl || !customerForm) return;
    customerForm.reset();
    if ($("custCountry") && !$("custCountry").value) $("custCountry").value = "Nigeria";
    if ($("custCity") && !$("custCity").value) $("custCity").value = "Lagos";
    if (customerToast) {
      customerToast.className = "toast-inline";
      customerToast.innerHTML = "";
    }
    bootstrap.Modal.getOrCreateInstance(customerModalEl).show();
  }

  if ($("addCustomerBtn")) {
    $("addCustomerBtn").addEventListener("click", openAddCustomer);
  }

  var flightModalEl = $("flightModal");
  var flightToast = $("flightToast");

  function showFlightToast(type, message) {
    if (!flightToast) return;
    flightToast.className = "toast-inline show" + (type === "error" ? " error" : "");
    flightToast.innerHTML =
      '<i class="bi ' +
      (type === "error" ? "bi-exclamation-circle-fill" : "bi-check-circle-fill") +
      '"></i> ' +
      escapeHTML(message);
  }

  function fillFlightCustomers() {
    var select = $("flightCustomer");
    if (!select) return;
    select.innerHTML = (DATA.customers || [])
      .map(function (customer) {
        return (
          '<option value="' +
          escapeHTML(customer.id) +
          '">' +
          escapeHTML((customer.firstName + " " + customer.lastName).trim()) +
          "</option>"
        );
      })
      .join("");
  }

  function openAddFlight() {
    if (!flightModalEl) return;
    var form = $("flightForm");
    if (form) form.reset();
    fillFlightCustomers();
    if ($("flightTime") && !$("flightTime").value) $("flightTime").value = "09:00";
    if ($("flightArrival") && !$("flightArrival").value) $("flightArrival").value = "16:00";
    if ($("flightDuration") && !$("flightDuration").value) $("flightDuration").value = "7h 00m";
    if (flightToast) {
      flightToast.className = "toast-inline";
      flightToast.innerHTML = "";
    }
    bootstrap.Modal.getOrCreateInstance(flightModalEl).show();
  }

  if ($("addFlightBtn")) {
    $("addFlightBtn").addEventListener("click", openAddFlight);
  }

  if ($("saveFlightBtn")) {
    $("saveFlightBtn").addEventListener("click", function () {
      var airline = $("flightAirline") ? $("flightAirline").value.trim() : "";
      var from = $("flightFrom") ? $("flightFrom").value.trim() : "";
      var to = $("flightTo") ? $("flightTo").value.trim() : "";
      var date = $("flightDate") ? $("flightDate").value : "";
      var amount = $("flightAmount") ? Number($("flightAmount").value) : 0;
      if (!airline || !from || !to || !date || !amount) {
        showFlightToast("error", "Airline, route, date, and amount are required.");
        return;
      }

      var button = $("saveFlightBtn");
      button.disabled = true;
      var createUrl = (window.CJ_API_URLS && window.CJ_API_URLS.createFlight) || "/admin/api/flights/create";
      var token = window.CJ_CSRF_TOKEN || "";

      fetch(createUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": token,
          "X-Requested-With": "XMLHttpRequest"
        },
        body: JSON.stringify({
          _token: token,
          customerId: $("flightCustomer") ? $("flightCustomer").value : "",
          airline: airline,
          flightNo: $("flightNumber") ? $("flightNumber").value.trim() : "",
          cabin: $("flightCabin") ? $("flightCabin").value : "Economy",
          from: from,
          to: to,
          date: date,
          time: $("flightTime") ? $("flightTime").value : "",
          arrival: $("flightArrival") ? $("flightArrival").value : "",
          duration: $("flightDuration") ? $("flightDuration").value.trim() : "",
          stops: $("flightStops") ? $("flightStops").value : "Direct",
          amount: amount,
          status: $("flightStatus") ? $("flightStatus").value : "Confirmed"
        })
      })
        .then(function (res) { return res.json(); })
        .then(function (res) {
          button.disabled = false;
          if (res && res.success && res.data) {
            DATA = res.data;
            refreshAfterChange();
            showFlightToast("success", res.message || "Flight added.");
            window.setTimeout(function () {
              var modal = bootstrap.Modal.getInstance(flightModalEl);
              if (modal) modal.hide();
            }, 600);
            return;
          }
          showFlightToast("error", (res && (res.error || res.message)) || "Could not add this flight.");
        })
        .catch(function () {
          button.disabled = false;
          showFlightToast("error", "Could not reach the server.");
        });
    });
  }

  var hotelModalEl = $("hotelModal");
  var hotelToast = $("hotelToast");

  function showHotelToast(type, message) {
    if (!hotelToast) return;
    hotelToast.className = "toast-inline show" + (type === "error" ? " error" : "");
    hotelToast.innerHTML =
      '<i class="bi ' +
      (type === "error" ? "bi-exclamation-circle-fill" : "bi-check-circle-fill") +
      '"></i> ' +
      escapeHTML(message);
  }

  function fillHotelCustomers() {
    var select = $("hotelCustomer");
    if (!select) return;
    select.innerHTML = (DATA.customers || [])
      .map(function (customer) {
        return (
          '<option value="' +
          escapeHTML(customer.id) +
          '">' +
          escapeHTML((customer.firstName + " " + customer.lastName).trim()) +
          "</option>"
        );
      })
      .join("");
  }

  function openAddHotel() {
    if (!hotelModalEl) return;
    var form = $("hotelForm");
    if (form) form.reset();
    fillHotelCustomers();
    if ($("hotelRoomType") && !$("hotelRoomType").value) $("hotelRoomType").value = "Deluxe Room";
    if ($("hotelGuests")) $("hotelGuests").value = "2";
    if (hotelToast) {
      hotelToast.className = "toast-inline";
      hotelToast.innerHTML = "";
    }
    bootstrap.Modal.getOrCreateInstance(hotelModalEl).show();
  }

  if ($("addHotelBtn")) {
    $("addHotelBtn").addEventListener("click", openAddHotel);
  }

  if ($("saveHotelBtn")) {
    $("saveHotelBtn").addEventListener("click", function () {
      var property = $("hotelProperty") ? $("hotelProperty").value.trim() : "";
      var location = $("hotelLocation") ? $("hotelLocation").value.trim() : "";
      var checkIn = $("hotelCheckIn") ? $("hotelCheckIn").value : "";
      var checkOut = $("hotelCheckOut") ? $("hotelCheckOut").value : "";
      var amount = $("hotelAmount") ? Number($("hotelAmount").value) : 0;
      if (!property || !location || !checkIn || !checkOut || !amount) {
        showHotelToast("error", "Property, location, dates, and amount are required.");
        return;
      }
      if (checkOut <= checkIn) {
        showHotelToast("error", "Check-out must be after check-in.");
        return;
      }

      var button = $("saveHotelBtn");
      button.disabled = true;
      var createUrl = (window.CJ_API_URLS && window.CJ_API_URLS.createHotel) || "/admin/api/hotels/create";
      var token = window.CJ_CSRF_TOKEN || "";

      fetch(createUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": token,
          "X-Requested-With": "XMLHttpRequest"
        },
        body: JSON.stringify({
          _token: token,
          customerId: $("hotelCustomer") ? $("hotelCustomer").value : "",
          property: property,
          location: location,
          roomType: $("hotelRoomType") ? $("hotelRoomType").value.trim() : "",
          guests: $("hotelGuests") ? $("hotelGuests").value : "2",
          checkIn: checkIn,
          checkOut: checkOut,
          amount: amount,
          status: $("hotelStatus") ? $("hotelStatus").value : "Confirmed"
        })
      })
        .then(function (res) { return res.json(); })
        .then(function (res) {
          button.disabled = false;
          if (res && res.success && res.data) {
            DATA = res.data;
            refreshAfterChange();
            showHotelToast("success", res.message || "Hotel added.");
            window.setTimeout(function () {
              var modal = bootstrap.Modal.getInstance(hotelModalEl);
              if (modal) modal.hide();
            }, 600);
            return;
          }
          showHotelToast("error", (res && (res.error || res.message)) || "Could not add this hotel.");
        })
        .catch(function () {
          button.disabled = false;
          showHotelToast("error", "Could not reach the server.");
        });
    });
  }

  var loanModalEl = $("loanModal");
  var loanToast = $("loanToast");

  function showLoanToast(type, message) {
    if (!loanToast) return;
    loanToast.className = "toast-inline show" + (type === "error" ? " error" : "");
    loanToast.innerHTML =
      '<i class="bi ' +
      (type === "error" ? "bi-exclamation-circle-fill" : "bi-check-circle-fill") +
      '"></i> ' +
      escapeHTML(message);
  }

  function fillLoanCustomers() {
    var select = $("loanCustomer");
    if (!select) return;
    select.innerHTML = (DATA.customers || [])
      .map(function (customer) {
        return (
          '<option value="' +
          escapeHTML(customer.id) +
          '">' +
          escapeHTML((customer.firstName + " " + customer.lastName).trim()) +
          "</option>"
        );
      })
      .join("");
  }

  function openAddLoan() {
    if (!loanModalEl) return;
    var form = $("loanForm");
    if (form) form.reset();
    fillLoanCustomers();
    if ($("loanPeriod")) $("loanPeriod").value = "6 months";
    if (loanToast) {
      loanToast.className = "toast-inline";
      loanToast.innerHTML = "";
    }
    bootstrap.Modal.getOrCreateInstance(loanModalEl).show();
  }

  if ($("addLoanBtn")) {
    $("addLoanBtn").addEventListener("click", openAddLoan);
  }

  if ($("saveLoanBtn")) {
    $("saveLoanBtn").addEventListener("click", function () {
      var amount = $("loanAmount") ? Number($("loanAmount").value) : 0;
      var purpose = $("loanPurpose") ? $("loanPurpose").value.trim() : "";
      var period = $("loanPeriod") ? $("loanPeriod").value : "";
      if (!amount || !purpose || !period) {
        showLoanToast("error", "Amount, period, and purpose are required.");
        return;
      }

      var button = $("saveLoanBtn");
      button.disabled = true;
      var createUrl = (window.CJ_API_URLS && window.CJ_API_URLS.createLoan) || "/admin/api/loans/create";
      var token = window.CJ_CSRF_TOKEN || "";

      fetch(createUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": token,
          "X-Requested-With": "XMLHttpRequest"
        },
        body: JSON.stringify({
          _token: token,
          customerId: $("loanCustomer") ? $("loanCustomer").value : "",
          amount: amount,
          period: period,
          purpose: purpose,
          note: $("loanNote") ? $("loanNote").value.trim() : "",
          status: $("loanStatus") ? $("loanStatus").value : "In review"
        })
      })
        .then(function (res) { return res.json(); })
        .then(function (res) {
          button.disabled = false;
          if (res && res.success && res.data) {
            DATA = res.data;
            refreshAfterChange();
            showLoanToast("success", res.message || "Enquiry added.");
            window.setTimeout(function () {
              var modal = bootstrap.Modal.getInstance(loanModalEl);
              if (modal) modal.hide();
            }, 600);
            return;
          }
          showLoanToast("error", (res && (res.error || res.message)) || "Could not add this enquiry.");
        })
        .catch(function () {
          button.disabled = false;
          showLoanToast("error", "Could not reach the server.");
        });
    });
  }

  if ($("saveCustomerBtn")) {
    $("saveCustomerBtn").addEventListener("click", function () {
      var first = $("custFirst") ? $("custFirst").value.trim() : "";
      var last = $("custLast") ? $("custLast").value.trim() : "";
      var email = $("custEmail") ? $("custEmail").value.trim() : "";
      if (!first || !last || !email) {
        showCustomerToast("error", "First name, last name, and email are required.");
        return;
      }

      var button = $("saveCustomerBtn");
      button.disabled = true;
      var createUrl = (window.CJ_API_URLS && window.CJ_API_URLS.createCustomer) || "/admin/api/customers/create";
      var token = window.CJ_CSRF_TOKEN || "";

      fetch(createUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": token,
          "X-Requested-With": "XMLHttpRequest"
        },
        body: JSON.stringify({
          _token: token,
          firstName: first,
          lastName: last,
          email: email,
          phone: $("custPhone") ? $("custPhone").value.trim() : "",
          country: $("custCountry") ? $("custCountry").value.trim() : "",
          city: $("custCity") ? $("custCity").value.trim() : "",
          tier: $("custTier") ? $("custTier").value : "Standard member",
          status: $("custStatus") ? $("custStatus").value : "Active"
        })
      })
        .then(function (res) { return res.json(); })
        .then(function (res) {
          button.disabled = false;
          if (res && res.success && res.data) {
            DATA = res.data;
            refreshAfterChange();
            showCustomerToast("success", res.message || "Customer added.");
            window.setTimeout(function () {
              var modal = bootstrap.Modal.getInstance(customerModalEl);
              if (modal) modal.hide();
            }, 600);
            return;
          }
          showCustomerToast("error", (res && (res.error || res.message)) || "Could not add this customer.");
        })
        .catch(function () {
          button.disabled = false;
          showCustomerToast("error", "Could not reach the server.");
        });
    });
  }

  /* =========================================================
     PAGE 7: TRANSACTIONS
     ========================================================= */
  var txnPageSize = 5;
  var txnState = { query: "", status: "", page: 1 };

  function renderTxnPager(total, pages) {
    var host = $("txnPager");
    if (!host) return;
    if (!total) {
      host.hidden = true;
      host.innerHTML = "";
      return;
    }
    host.hidden = false;
    var start = (txnState.page - 1) * txnPageSize + 1;
    var end = Math.min(total, txnState.page * txnPageSize);
    var buttons =
      '<button type="button" class="pager-btn" data-txn-page="prev"' +
      (txnState.page <= 1 ? " disabled" : "") +
      ">Previous</button>";
    for (var i = 1; i <= pages; i++) {
      buttons +=
        '<button type="button" class="pager-btn' +
        (i === txnState.page ? " active" : "") +
        '" data-txn-page="' +
        i +
        '"' +
        (i === txnState.page ? ' aria-current="page"' : "") +
        ">" +
        i +
        "</button>";
    }
    buttons +=
      '<button type="button" class="pager-btn" data-txn-page="next"' +
      (txnState.page >= pages ? " disabled" : "") +
      ">Next</button>";
    host.innerHTML =
      '<span class="pager-meta">Showing ' +
      start +
      "–" +
      end +
      " of " +
      total +
      "</span>" +
      '<div class="pager-controls">' +
      buttons +
      "</div>";
  }

  function renderTransactions() {
    var host = $("transactionsBody");
    if (!host) return;
    var q = txnState.query.toLowerCase().trim();
    var filtered = DATA.transactions.filter(function (t) {
      if (txnState.status && t.status !== txnState.status) return false;
      if (!q) return true;
      var hay = (
        t.ref +
        " " +
        customerName(t.customerId) +
        " " +
        t.related +
        " " +
        t.method
      ).toLowerCase();
      return hay.indexOf(q) !== -1;
    });
    if ($("txnCount")) $("txnCount").textContent = String(filtered.length);
    var pages = Math.max(1, Math.ceil(filtered.length / txnPageSize));
    if (txnState.page > pages) txnState.page = pages;
    if (txnState.page < 1) txnState.page = 1;
    var pageRows = filtered.slice(
      (txnState.page - 1) * txnPageSize,
      txnState.page * txnPageSize
    );
    renderTxnPager(filtered.length, pages);
    if (!pageRows.length) {
      host.innerHTML = emptyRow(8, "No transactions match this search.");
      return;
    }

    host.innerHTML = pageRows
      .map(function (t) {
        return (
          "<tr>" +
          '<td data-label="Reference"><span class="cell-mono">' +
          escapeHTML(t.ref) +
          "</span></td>" +
          '<td data-label="Customer"><span class="cell-strong">' +
          escapeHTML(customerName(t.customerId)) +
          "</span></td>" +
          '<td data-label="Related booking"><span class="cell-strong">' +
          escapeHTML(t.related) +
          "</span></td>" +
          '<td data-label="Method"><span class="cell-strong">' +
          escapeHTML(t.method) +
          "</span></td>" +
          '<td data-label="Date"><span class="cell-strong">' +
          escapeHTML(formatDate(t.date)) +
          "</span></td>" +
          '<td data-label="Amount"><span class="cell-mono">' +
          escapeHTML(formatMoney(t.amount)) +
          "</span></td>" +
          '<td data-label="Status">' +
          statusPill(t.status) +
          "</td>" +
          '<td class="text-end"><div class="row-actions">' +
          '<button type="button" class="btn btn-ghost btn-icon js-view" data-kind="transaction" data-id="' +
          escapeHTML(t.id) +
          '" aria-label="View transaction"><i class="bi bi-eye"></i></button>' +
          '<button type="button" class="btn btn-soft btn-icon js-edit" data-kind="transaction" data-id="' +
          escapeHTML(t.id) +
          '" aria-label="Edit transaction"><i class="bi bi-pencil"></i></button>' +
          '<button type="button" class="btn btn-danger-soft btn-icon js-delete" data-kind="transaction" data-id="' +
          escapeHTML(t.id) +
          '" aria-label="Delete transaction"><i class="bi bi-trash3"></i></button>' +
          "</div></td>" +
          "</tr>"
        );
      })
      .join("");
  }

  var transactionModalEl = $("transactionModal");
  var txnToast = $("txnToast");

  function showTxnToast(type, message) {
    if (!txnToast) return;
    txnToast.className = "toast-inline show" + (type === "error" ? " error" : "");
    txnToast.innerHTML =
      '<i class="bi ' +
      (type === "error" ? "bi-exclamation-circle-fill" : "bi-check-circle-fill") +
      '"></i> ' +
      escapeHTML(message);
  }

  function fillTxnCustomers() {
    var select = $("txnCustomer");
    if (!select) return;
    select.innerHTML = (DATA.customers || [])
      .map(function (customer) {
        return (
          '<option value="' +
          escapeHTML(customer.id) +
          '">' +
          escapeHTML((customer.firstName + " " + customer.lastName).trim()) +
          "</option>"
        );
      })
      .join("");
  }

  function openAddTransaction() {
    if (!transactionModalEl) return;
    var form = $("transactionForm");
    if (form) form.reset();
    fillTxnCustomers();
    if ($("txnDate")) $("txnDate").value = new Date().toISOString().slice(0, 10);
    if ($("txnMethod")) $("txnMethod").value = "Card";
    if ($("txnStatus")) $("txnStatus").value = "Paid";
    if (txnToast) {
      txnToast.className = "toast-inline";
      txnToast.innerHTML = "";
    }
    bootstrap.Modal.getOrCreateInstance(transactionModalEl).show();
  }

  if ($("addTransactionBtn")) {
    $("addTransactionBtn").addEventListener("click", openAddTransaction);
  }

  if ($("saveTransactionBtn")) {
    $("saveTransactionBtn").addEventListener("click", function () {
      var amount = $("txnAmount") ? Number($("txnAmount").value) : 0;
      var method = $("txnMethod") ? $("txnMethod").value : "";
      var customerId = $("txnCustomer") ? $("txnCustomer").value : "";
      if (!customerId || !amount || !method) {
        showTxnToast("error", "Customer, amount, and payment method are required.");
        return;
      }

      var button = $("saveTransactionBtn");
      button.disabled = true;
      var createUrl = (window.CJ_API_URLS && window.CJ_API_URLS.createTransaction) || "/admin/api/transactions/create";
      var token = window.CJ_CSRF_TOKEN || "";

      fetch(createUrl, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": token,
          "X-Requested-With": "XMLHttpRequest"
        },
        body: JSON.stringify({
          _token: token,
          customerId: customerId,
          amount: amount,
          method: method,
          date: $("txnDate") ? $("txnDate").value : "",
          status: $("txnStatus") ? $("txnStatus").value : "Paid",
          related: $("txnRelated") ? $("txnRelated").value.trim() : ""
        })
      })
        .then(function (res) { return res.json(); })
        .then(function (res) {
          button.disabled = false;
          if (res && res.success && res.data) {
            DATA = res.data;
            txnState.page = 1;
            refreshAfterChange();
            showTxnToast("success", res.message || "Transaction added.");
            window.setTimeout(function () {
              var modal = bootstrap.Modal.getInstance(transactionModalEl);
              if (modal) modal.hide();
            }, 600);
            return;
          }
          showTxnToast("error", (res && (res.error || res.message)) || "Could not add this transaction.");
        })
        .catch(function () {
          button.disabled = false;
          showTxnToast("error", "Could not reach the server.");
        });
    });
  }

  /* =========================================================
     PAGE 8: REPORTS & ANALYTICS
     ========================================================= */
  function renderReports() {
    var totalBookings = DATA.flights.length + DATA.hotels.length;
    var paidRevenue = DATA.transactions
      .filter(function (t) {
        return t.status === "Paid";
      })
      .reduce(function (s, t) {
        return s + t.amount;
      }, 0);
    var loanValue = DATA.loans.reduce(function (s, l) {
      return s + l.amount;
    }, 0);

    if ($("reportCustomers"))
      $("reportCustomers").textContent = String(DATA.customers.length);
    if ($("reportBookings"))
      $("reportBookings").textContent = String(totalBookings);
    if ($("reportRevenue"))
      $("reportRevenue").textContent = shortMoney(paidRevenue);
    if ($("reportLoanValue"))
      $("reportLoanValue").textContent = shortMoney(loanValue);
    if ($("reportUpdated")) {
      $("reportUpdated").textContent =
        " Last refreshed " +
        new Date().toLocaleTimeString("en-GB", {
          hour: "2-digit",
          minute: "2-digit",
          second: "2-digit",
        }) +
        ".";
    }

    renderBookingChart("reportChart", "reportChartLabels");
    renderReportPayments();

    var counts = {};
    DATA.flights.forEach(function (f) {
      counts[f.continent] = (counts[f.continent] || 0) + 1;
    });
    DATA.hotels.forEach(function (h) {
      counts[h.continent] = (counts[h.continent] || 0) + 1;
    });

    var palette = {
      Africa: "#0b1e3d",
      Europe: "#1e3657",
      Asia: "#a8842c",
      "Middle East": "#c9a55b",
      Americas: "#2e7d5b",
      Oceania: "#8a8378",
    };
    var keys = Object.keys(counts);
    var total = keys.reduce(function (s, k) {
      return s + counts[k];
    }, 0);

    var donut = $("continentDonut");
    var legend = $("continentLegend");
    var totalEl = $("continentDonutTotal");
    if (totalEl) totalEl.textContent = String(total);
    if (!donut || !legend) return;

    if (!total) {
      donut.style.background = "var(--hairline-soft)";
      legend.innerHTML =
        "<p style=\"font-family:'DM Sans',sans-serif;font-size:12px;color:var(--muted);margin:0;\">No data.</p>";
      return;
    }

    var cursor = 0,
      stops = [];
    keys.forEach(function (key) {
      var start = (cursor / total) * 360;
      cursor += counts[key];
      var end = (cursor / total) * 360;
      stops.push(
        (palette[key] || "#8a8378") + " " + start + "deg " + end + "deg",
      );
    });
    donut.style.background = "conic-gradient(" + stops.join(", ") + ")";
    legend.innerHTML = keys
      .map(function (key) {
        return (
          '<div class="donut-legend-item">' +
          '<span class="dot" style="background:' +
          (palette[key] || "#8a8378") +
          ';"></span>' +
          "<span>" +
          escapeHTML(key) +
          "</span>" +
          '<span class="count">' +
          counts[key] +
          "</span>" +
          "</div>"
        );
      })
      .join("");
  }

  function renderReportPayments() {
    var host = $("reportPaymentsBody");
    if (!host) return;
    var totals = {};
    ["Paid", "Pending", "Refunded", "Failed"].forEach(function (status) {
      totals[status] = { count: 0, amount: 0 };
    });
    (DATA.transactions || []).forEach(function (transaction) {
      var status = totals[transaction.status] ? transaction.status : "Other";
      if (!totals[status]) totals[status] = { count: 0, amount: 0 };
      totals[status].count += 1;
      totals[status].amount += Number(transaction.amount) || 0;
    });
    var statuses = Object.keys(totals).filter(function (status) {
      return totals[status].count > 0;
    });
    if (!statuses.length) {
      host.innerHTML = emptyRow(3, "No payments have been recorded yet.");
      return;
    }
    host.innerHTML = statuses
      .map(function (status) {
        return (
          "<tr>" +
          '<td data-label="Status">' +
          statusPill(status) +
          "</td>" +
          '<td data-label="Transactions"><span class="cell-strong">' +
          totals[status].count +
          "</span></td>" +
          '<td data-label="Amount"><span class="cell-mono">' +
          escapeHTML(formatMoney(totals[status].amount)) +
          "</span></td>" +
          "</tr>"
        );
      })
      .join("");
  }

  if ($("exportReportBtn")) {
    $("exportReportBtn").addEventListener("click", function () {
      window.location.href =
        (window.CJ_API_URLS && window.CJ_API_URLS.exportReport) ||
        "/admin/reports/export";
    });
  }

  /* =========================================================
     PAGE 10: API INTEGRATIONS
     ========================================================= */
  var apiEls = {
    flightBody: $("flightProvidersBody"),
    hotelBody: $("hotelProvidersBody"),
    flightEmpty: $("flightEmpty"),
    hotelEmpty: $("hotelEmpty"),
    flightCount: $("flightProviderCount"),
    hotelCount: $("hotelProviderCount"),
    logBody: $("apiLogBody"),
    logEmpty: $("apiLogEmpty"),
    logCount: $("apiLogCount"),
    modalEl: $("apiProviderModal"),
    modalTitle: $("apiProviderModalTitle"),
    nameInput: $("apiName"),
    urlInput: $("apiBaseUrl"),
    keyInput: $("apiKey"),
    secretInput: $("apiSecret"),
    envSelect: $("apiEnv"),
    enabledCheck: $("apiEnabled"),
    keyHint: $("apiKeyHint"),
    secretHint: $("apiSecretHint"),
    saveBtn: $("apiSaveBtn"),
    deleteEl: $("apiDeleteModal"),
    deleteName: $("apiDeleteName"),
    deleteConfirm: $("apiDeleteConfirm"),
    badge: $("badgeApi"),
  };

  function apiProviders() {
    return DATA.apiProviders || [];
  }

  function getProvider(id) {
    var providers = apiProviders();
    for (var i = 0; i < providers.length; i++) {
      if (providers[i].id === id) return providers[i];
    }
    return null;
  }

  function showApiToast(type, message) {
    var toast = $("apiToast");
    if (!toast) return;
    toast.className = "toast-inline show" + (type === "error" ? " error" : "");
    toast.innerHTML =
      '<i class="bi ' +
      (type === "error" ? "bi-exclamation-circle-fill" : "bi-check-circle-fill") +
      '"></i> ' +
      escapeHTML(message);
  }

  function postApi(url, payload) {
    var token = window.CJ_CSRF_TOKEN || "";
    return fetch(url, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "Accept": "application/json",
        "X-CSRF-TOKEN": token,
        "X-Requested-With": "XMLHttpRequest"
      },
      body: JSON.stringify(Object.assign({ _token: token }, payload))
    }).then(function (res) { return res.json(); });
  }

  function maskValue(raw) {
    var str = String(raw || "");
    if (!str) return "";
    return "••••••••" + str.slice(-4);
  }

  function statusMeta(status) {
    switch (status) {
      case "Connected":
        return { cls: "api-status-connected", label: "Connected" };
      case "Error":
        return { cls: "api-status-failed", label: "Connection failed" };
      default:
        return { cls: "api-status-untested", label: "Not tested" };
    }
  }

  function categoryIcon(cat) {
    return cat === "Flight" ? "bi-airplane-fill" : "bi-building-fill";
  }

  function providerRow(p) {
    var sm = statusMeta(p.status);
    var isTesting = apiState.testing[p.id] === true;
    var statusHTML = isTesting
      ? '<span class="api-status api-status-testing"><span class="api-dot"></span>Testing…</span>'
      : '<span class="api-status ' +
        sm.cls +
        '"><span class="api-dot"></span>' +
        sm.label +
        "</span>";
    var lastTestHTML = p.lastTest
      ? '<span class="api-last-test"><strong>' +
        escapeHTML(formatDateTime(p.lastTest)) +
        "</strong></span>"
      : '<span class="api-last-test">Never</span>';
    var serviceClass =
      p.category === "Flight" ? "api-service-flight" : "api-service-hotel";
    var serviceIcon =
      p.category === "Flight" ? "bi-airplane-fill" : "bi-building-fill";

    return (
      '<tr data-id="' +
      escapeHTML(p.id) +
      '">' +
      '<td data-label="Provider"><div class="api-provider-name"><span class="api-provider-icon' +
      (p.category === "Hotel" ? " is-hotel" : "") +
      '"><i class="bi ' +
      categoryIcon(p.category) +
      '"></i></span><div><strong>' +
      escapeHTML(p.name) +
      '</strong><span class="api-url-preview" title="' +
      escapeHTML(p.baseUrl) +
      '">' +
      escapeHTML(p.baseUrl) +
      "</span></div></div></td>" +
      '<td data-label="Service"><span class="api-service-badge ' +
      serviceClass +
      '"><i class="bi ' +
      serviceIcon +
      '"></i> ' +
      escapeHTML(p.category) +
      "</span></td>" +
      '<td data-label="Environment"><span class="api-env-pill api-env-' +
      escapeHTML(String(p.env || "Sandbox").toLowerCase()) +
      '">' +
      escapeHTML(p.env) +
      "</span></td>" +
      '<td data-label="Status">' +
      statusHTML +
      "</td>" +
      '<td data-label="Last test">' +
      lastTestHTML +
      "</td>" +
      '<td data-label="Active"><span class="api-toggle ' +
      (p.enabled ? "is-on" : "is-off") +
      '"><i class="bi ' +
      (p.enabled ? "bi-check-circle-fill" : "bi-dash-circle-fill") +
      '"></i>' +
      (p.enabled ? "Active" : "Inactive") +
      "</span></td>" +
      '<td class="text-end"><div class="api-actions">' +
      '<button type="button" class="btn btn-ghost btn-icon js-api-test" data-id="' +
      escapeHTML(p.id) +
      '" aria-label="Test connection" title="Test connection"><i class="bi bi-plug-fill"></i></button>' +
      '<button type="button" class="btn btn-soft btn-icon js-api-edit" data-id="' +
      escapeHTML(p.id) +
      '" aria-label="Edit provider" title="Edit"><i class="bi bi-pencil"></i></button>' +
      '<button type="button" class="btn btn-danger-soft btn-icon js-api-delete" data-id="' +
      escapeHTML(p.id) +
      '" aria-label="Remove provider" title="Remove"><i class="bi bi-trash3"></i></button>' +
      "</div></td>" +
      "</tr>"
    );
  }

  function renderProviders() {
    var flights = apiProviders().filter(function (p) {
      return p.category === "Flight";
    });
    var hotels = apiProviders().filter(function (p) {
      return p.category === "Hotel";
    });
    if (apiEls.flightBody)
      apiEls.flightBody.innerHTML = flights.map(providerRow).join("");
    if (apiEls.flightEmpty)
      apiEls.flightEmpty.classList.toggle("is-visible", flights.length === 0);
    if (apiEls.flightCount)
      apiEls.flightCount.textContent =
        flights.length + (flights.length === 1 ? " provider" : " providers");
    if (apiEls.hotelBody)
      apiEls.hotelBody.innerHTML = hotels.map(providerRow).join("");
    if (apiEls.hotelEmpty)
      apiEls.hotelEmpty.classList.toggle("is-visible", hotels.length === 0);
    if (apiEls.hotelCount)
      apiEls.hotelCount.textContent =
        hotels.length + (hotels.length === 1 ? " provider" : " providers");
    if (apiEls.badge)
      apiEls.badge.textContent = String(apiProviders().length);
  }

  function renderLogs() {
    if (!apiEls.logBody) return;
    var sorted = (DATA.apiLogs || []).slice().sort(function (a, b) {
      return new Date(b.ts) - new Date(a.ts);
    });
    if (apiEls.logCount)
      apiEls.logCount.textContent =
        sorted.length + (sorted.length === 1 ? " event" : " events");
    if (!sorted.length) {
      apiEls.logBody.innerHTML = "";
      if (apiEls.logEmpty) apiEls.logEmpty.classList.add("is-visible");
      return;
    }
    if (apiEls.logEmpty) apiEls.logEmpty.classList.remove("is-visible");
    apiEls.logBody.innerHTML = sorted
      .map(function (l) {
        var statusCls =
          l.status === "Success"
            ? "status-confirmed"
            : l.status === "Failed"
              ? "status-cancelled"
              : "status-completed";
        var statusIcon =
          l.status === "Success"
            ? "bi-check-circle-fill"
            : l.status === "Failed"
              ? "bi-x-circle-fill"
              : "bi-info-circle-fill";
        return (
          "<tr>" +
          '<td data-label="Date & time"><span class="api-log-mono">' +
          escapeHTML(formatDateTime(l.ts)) +
          "</span></td>" +
          '<td data-label="Provider"><span class="cell-strong">' +
          escapeHTML(l.provider) +
          "</span></td>" +
          '<td data-label="Service"><span class="cell-strong">' +
          escapeHTML(l.service) +
          "</span></td>" +
          '<td data-label="Activity"><span class="cell-strong">' +
          escapeHTML(l.activity) +
          "</span></td>" +
          '<td data-label="Status"><span class="status ' +
          statusCls +
          '"><i class="bi ' +
          statusIcon +
          '"></i> ' +
          escapeHTML(l.status) +
          "</span></td>" +
          "</tr>"
        );
      })
      .join("");
  }

  function renderAllApi() {
    renderProviders();
    renderLogs();
    refreshCounts();
  }

  function openAdd(category) {
    if (!apiEls.modalEl) return;
    apiState.editingId = null;
    apiState.editingCategory = category;
    if (apiEls.modalTitle)
      apiEls.modalTitle.innerHTML =
        '<i class="bi bi-plug-fill"></i> Add ' +
        (category === "Flight" ? "flight" : "hotel") +
        " API provider";
    if (apiEls.nameInput) apiEls.nameInput.value = "";
    if (apiEls.urlInput) apiEls.urlInput.value = "";
    if (apiEls.keyInput) {
      apiEls.keyInput.value = "";
      apiEls.keyInput.placeholder = "Enter API key";
    }
    if (apiEls.secretInput) {
      apiEls.secretInput.value = "";
      apiEls.secretInput.placeholder = "Enter API secret";
    }
    if (apiEls.envSelect) apiEls.envSelect.value = "Sandbox";
    if (apiEls.enabledCheck) apiEls.enabledCheck.checked = true;
    if (apiEls.keyHint) apiEls.keyHint.hidden = true;
    if (apiEls.secretHint) apiEls.secretHint.hidden = true;
    if ($("apiToast")) {
      $("apiToast").className = "toast-inline";
      $("apiToast").innerHTML = "";
    }
    clearApiErrors();
    resetApiToggles();
    bootstrap.Modal.getOrCreateInstance(apiEls.modalEl).show();
  }

  function openEditApi(id) {
    var p = getProvider(id);
    if (!p || !apiEls.modalEl) return;
    apiState.editingId = id;
    apiState.editingCategory = p.category;
    if (apiEls.modalTitle)
      apiEls.modalTitle.innerHTML =
        '<i class="bi bi-pencil-square"></i> Edit ' +
        (p.category === "Flight" ? "flight" : "hotel") +
        " API provider";
    if (apiEls.nameInput) apiEls.nameInput.value = p.name;
    if (apiEls.urlInput) apiEls.urlInput.value = p.baseUrl;
    if (apiEls.keyInput) {
      apiEls.keyInput.value = "";
      apiEls.keyInput.placeholder = p.apiKeyMasked || "Enter API key";
    }
    if (apiEls.secretInput) {
      apiEls.secretInput.value = "";
      apiEls.secretInput.placeholder = p.secretMasked || "Enter API secret";
    }
    if (apiEls.envSelect) apiEls.envSelect.value = p.env;
    if (apiEls.enabledCheck) apiEls.enabledCheck.checked = p.enabled;
    if (apiEls.keyHint) apiEls.keyHint.hidden = false;
    if (apiEls.secretHint) apiEls.secretHint.hidden = false;
    if ($("apiToast")) {
      $("apiToast").className = "toast-inline";
      $("apiToast").innerHTML = "";
    }
    clearApiErrors();
    resetApiToggles();
    bootstrap.Modal.getOrCreateInstance(apiEls.modalEl).show();
  }

  function clearApiErrors() {
    [
      apiEls.nameInput,
      apiEls.urlInput,
      apiEls.keyInput,
      apiEls.secretInput,
    ].forEach(function (el) {
      if (el) el.classList.remove("is-invalid");
    });
  }

  function resetApiToggles() {
    document.querySelectorAll(".cred-toggle").forEach(function (btn) {
      var target = document.getElementById(btn.dataset.target);
      if (target) target.type = "password";
      var icon = btn.querySelector("i");
      if (icon) icon.className = "bi bi-eye-fill";
      btn.setAttribute("aria-pressed", "false");
      btn.setAttribute("aria-label", "Show");
    });
  }

  function saveProvider() {
    var name = apiEls.nameInput ? apiEls.nameInput.value.trim() : "";
    var url = apiEls.urlInput ? apiEls.urlInput.value.trim() : "";
    var key = apiEls.keyInput ? apiEls.keyInput.value : "";
    var secret = apiEls.secretInput ? apiEls.secretInput.value : "";
    var env = apiEls.envSelect ? apiEls.envSelect.value : "Sandbox";
    var enabled = apiEls.enabledCheck ? apiEls.enabledCheck.checked : true;
    var valid = true;
    if (!name) {
      if (apiEls.nameInput) apiEls.nameInput.classList.add("is-invalid");
      valid = false;
    } else if (apiEls.nameInput)
      apiEls.nameInput.classList.remove("is-invalid");
    if (!url || !/^https?:\/\//i.test(url)) {
      if (apiEls.urlInput) apiEls.urlInput.classList.add("is-invalid");
      valid = false;
    } else if (apiEls.urlInput) apiEls.urlInput.classList.remove("is-invalid");
    var isEdit = !!apiState.editingId;
    if (!isEdit && !key) {
      if (apiEls.keyInput) apiEls.keyInput.classList.add("is-invalid");
      valid = false;
    } else if (apiEls.keyInput) apiEls.keyInput.classList.remove("is-invalid");
    if (!valid) return;

    var saveUrl = (window.CJ_API_URLS && window.CJ_API_URLS.saveProvider) || "/admin/api/providers/save";
    if (apiEls.saveBtn) apiEls.saveBtn.disabled = true;
    postApi(saveUrl, {
      id: isEdit ? apiState.editingId : "",
      name: name,
      category: isEdit && getProvider(apiState.editingId)
        ? getProvider(apiState.editingId).category
        : apiState.editingCategory,
      baseUrl: url,
      env: env,
      enabled: enabled,
      apiKey: key,
      apiSecret: secret
    })
      .then(function (res) {
        if (apiEls.saveBtn) apiEls.saveBtn.disabled = false;
        key = "";
        secret = "";
        if (res && res.success && res.data) {
          DATA = res.data;
          refreshAfterChange();
          if (apiEls.modalEl) {
            var modal = bootstrap.Modal.getInstance(apiEls.modalEl);
            if (modal) modal.hide();
          }
          return;
        }
        showApiToast("error", (res && (res.error || res.message)) || "Could not save this provider.");
      })
      .catch(function () {
        if (apiEls.saveBtn) apiEls.saveBtn.disabled = false;
        showApiToast("error", "Could not reach the server.");
      });
  }

  function testConnection(id) {
    var p = getProvider(id);
    if (!p || apiState.testing[id]) return;
    apiState.testing[id] = true;
    renderProviders();
    var testUrl = (window.CJ_API_URLS && window.CJ_API_URLS.testProvider) || "/admin/api/providers/test";
    postApi(testUrl, { id: id })
      .then(function (res) {
        delete apiState.testing[id];
        if (res && res.data) {
          DATA = res.data;
          refreshAfterChange();
        } else {
          renderAllApi();
        }
      })
      .catch(function () {
        delete apiState.testing[id];
        renderAllApi();
      });
  }

  function openDeleteApi(id) {
    var p = getProvider(id);
    if (!p || !apiEls.deleteEl) return;
    apiState.deleteId = id;
    if (apiEls.deleteName) apiEls.deleteName.textContent = p.name;
    bootstrap.Modal.getOrCreateInstance(apiEls.deleteEl).show();
  }

  function confirmDeleteApi() {
    var id = apiState.deleteId;
    if (!id) return;
    var deleteUrl = (window.CJ_API_URLS && window.CJ_API_URLS.deleteProvider) || "/admin/api/providers/delete";
    var button = apiEls.deleteConfirm;
    if (button) button.disabled = true;
    postApi(deleteUrl, { id: id })
      .then(function (res) {
        if (button) button.disabled = false;
        apiState.deleteId = null;
        if (res && res.success && res.data) {
          DATA = res.data;
          if (apiEls.deleteEl) {
            var modal = bootstrap.Modal.getInstance(apiEls.deleteEl);
            if (modal) modal.hide();
          }
          refreshAfterChange();
          return;
        }
        window.alert((res && (res.error || res.message)) || "Could not remove this provider.");
      })
      .catch(function () {
        if (button) button.disabled = false;
        window.alert("Could not reach the server.");
      });
  }

  document.addEventListener("click", function (event) {
    var addBtn = event.target.closest("[data-api-add]");
    if (addBtn) {
      openAdd(addBtn.dataset.apiAdd);
      return;
    }
    var editBtn = event.target.closest(".js-api-edit");
    if (editBtn) {
      openEditApi(editBtn.dataset.id);
      return;
    }
    var testBtn = event.target.closest(".js-api-test");
    if (testBtn) {
      testConnection(testBtn.dataset.id);
      return;
    }
    var deleteBtn = event.target.closest(".js-api-delete");
    if (deleteBtn) {
      openDeleteApi(deleteBtn.dataset.id);
      return;
    }
    var toggle = event.target.closest(".cred-toggle");
    if (toggle) {
      var target = document.getElementById(toggle.dataset.target);
      if (target) {
        var isPassword = target.type === "password";
        target.type = isPassword ? "text" : "password";
        var icon = toggle.querySelector("i");
        if (icon)
          icon.className = isPassword
            ? "bi bi-eye-slash-fill"
            : "bi bi-eye-fill";
        toggle.setAttribute("aria-pressed", isPassword ? "true" : "false");
        toggle.setAttribute("aria-label", isPassword ? "Hide" : "Show");
      }
    }
  });

  if (apiEls.saveBtn) apiEls.saveBtn.addEventListener("click", saveProvider);
  if (apiEls.deleteConfirm)
    apiEls.deleteConfirm.addEventListener("click", confirmDeleteApi);

  [
    apiEls.nameInput,
    apiEls.urlInput,
    apiEls.keyInput,
    apiEls.secretInput,
  ].forEach(function (el) {
    if (!el) return;
    el.addEventListener("input", function () {
      el.classList.remove("is-invalid");
    });
  });

  var refreshBtn = document.getElementById("apiRefreshBtn");
  if (refreshBtn)
    refreshBtn.addEventListener("click", function () {
      syncLiveMySQLData();
    });

  /* =========================================================
     GLOBAL & COMMON CONTROLS
     ========================================================= */
  function bindSearch(inputId, filterId, stateObj, renderFn) {
    var input = $(inputId),
      filter = $(filterId);
    if (input)
      input.addEventListener("input", function () {
        stateObj.query = input.value;
        if (typeof stateObj.page === "number") stateObj.page = 1;
        renderFn();
      });
    if (filter)
      filter.addEventListener("change", function () {
        stateObj.status = filter.value;
        if (typeof stateObj.page === "number") stateObj.page = 1;
        renderFn();
      });
  }

  bindSearch(
    "customerSearch",
    "customerStatusFilter",
    customerState,
    renderCustomers,
  );
  bindSearch("flightSearch", "flightStatusFilter", flightState, renderFlights);
  bindSearch("hotelSearch", "hotelStatusFilter", hotelState, renderHotels);
  bindSearch("loanSearch", "loanStatusFilter", loanState, renderLoans);
  bindSearch("txnSearch", "txnStatusFilter", txnState, renderTransactions);

  if ($("destinationSearch"))
    $("destinationSearch").addEventListener("input", function () {
      destState.query = this.value;
      renderDestinations();
    });
  if ($("destinationContinentFilter"))
    $("destinationContinentFilter").addEventListener("change", function () {
      destState.continent = this.value;
      renderDestinations();
    });

  if ($("notificationsBtn")) {
    $("notificationsBtn").addEventListener("click", function () {
      if (!detailModalEl || !detailTitle || !detailBody) return;
      var items = DATA.activities || [];
      detailTitle.innerHTML = '<i class="bi bi-bell-fill"></i> Notifications';
      detailBody.innerHTML = items.length
        ? '<p style="font-size:14px;color:var(--ink-soft);margin-bottom:20px;line-height:1.7;">Latest activity saved in the database.</p>' +
          '<ul class="activity-list">' +
          items.map(function (item) {
            var tone = item.tone ? " " + item.tone : "";
            return (
              '<li class="activity-item"><span class="activity-icon' +
              tone +
              '"><i class="bi ' +
              escapeHTML(item.icon || "bi-bell-fill") +
              '"></i></span><div class="activity-body"><strong>' +
              escapeHTML(item.title) +
              "</strong><span>" +
              escapeHTML(item.text) +
              "</span></div><span class=\"activity-time\">" +
              escapeHTML(item.time) +
              "</span></li>"
            );
          }).join("") +
          "</ul>"
        : '<p style="font-size:14px;color:var(--ink-soft);margin:0;">No activity has been recorded yet.</p>';
      bootstrap.Modal.getOrCreateInstance(detailModalEl).show();
    });
  }

  if ($("helpBtn")) {
    $("helpBtn").addEventListener("click", function () {
      window.location.href = "../contact";
    });
  }

  function refreshAfterChange() {
    saveData();
    refreshCounts();
    if ($("statCustomers")) renderDashboardStats();
    if ($("statusDonut")) renderStatusDonut();
    if ($("txnChart")) renderTxnOverview();
    if ($("recentBookingsBody")) renderRecentBookings();
    if ($("customersBody")) renderCustomers();
    if ($("flightsBody")) renderFlights();
    if ($("hotelsBody")) renderHotels();
    if ($("loansBody")) renderLoans();
    if ($("destinationsGrid")) renderDestinations();
    if ($("transactionsBody")) renderTransactions();
    if ($("reportChart")) renderReports();
    if ($("flightProvidersBody") || $("hotelProvidersBody")) renderAllApi();
  }

  function initPage() {
    if ($("year")) $("year").textContent = new Date().getFullYear();
    refreshCounts();

    // Dashboard page elements
    if ($("statCustomers") || $("bookingChart")) {
      renderDashboardStats();
      renderBookingChart("bookingChart", "bookingChartLabels");
      renderStatusDonut();
      renderTxnOverview();
      renderActivity();
      renderRecentBookings();
    }

    // Customers page
    if ($("customersBody")) renderCustomers();

    // Flights page
    if ($("flightsBody")) renderFlights();

    // Hotels page
    if ($("hotelsBody")) renderHotels();

    // Loans page
    if ($("loansBody")) renderLoans();

    // Destinations page
    if ($("destinationsGrid")) renderDestinations();

    // Transactions page
    if ($("transactionsBody")) renderTransactions();

    // Reports page
    if ($("reportChart")) renderReports();

    // API Integrations page
    if (apiEls.flightBody || apiEls.hotelBody) renderAllApi();

    if (window.CJ_API_URLS && window.CJ_API_URLS.liveData) {
      window.setInterval(function () {
        if (!document.hidden) syncLiveMySQLData();
      }, 8000);
    }
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initPage);
  } else {
    initPage();
  }
})();
