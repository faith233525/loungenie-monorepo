(function(){
  'use strict';

  function qs(id){return document.getElementById(id);} 
  function qsa(sel){return document.querySelectorAll(sel);} 

  function initUnitsFilters(){
    var companySel = qs('unitsCompanyFilter');
    var statusSel = qs('unitsStatusFilter');
    var searchInput = qs('unitsSearchInput');
    var unitCards = qsa('#units .lgp-grid .lgp-card.lgp-card-compact');

    if(!unitCards.length){return;}

    function applyUnitsFilters(){
      var company = companySel && companySel.value || 'all';
      var status = statusSel && statusSel.value || 'all';
      var search = searchInput && (searchInput.value || '').toLowerCase() || '';
      unitCards.forEach(function(card){
        var c = card.getAttribute('data-company');
        var s = card.getAttribute('data-status');
        var n = (card.getAttribute('data-name') || '').toLowerCase();
        var visible = true;
        if(company !== 'all' && c !== company) visible = false;
        if(status !== 'all' && s !== status) visible = false;
        if(search && n.indexOf(search) === -1) visible = false;
        card.style.display = visible ? '' : 'none';
      });
    }

    if(companySel) companySel.addEventListener('change', applyUnitsFilters);
    if(statusSel) statusSel.addEventListener('change', applyUnitsFilters);
    if(searchInput) searchInput.addEventListener('input', applyUnitsFilters);
    applyUnitsFilters();
  }

  function initTicketsFilters(){
    var companySel = qs('ticketsCompanyFilter');
    var unitSel = qs('ticketsUnitFilter');
    var statusSel = qs('ticketsStatusFilter');
    var prioritySel = qs('ticketsPriorityFilter');
    var rows = qsa('#tickets table.lgp-table tbody tr');
    if(!rows.length){return;}

    function applyTicketsFilters(){
      var company = companySel && companySel.value || 'all';
      var unit = unitSel && unitSel.value || 'all';
      var status = statusSel && statusSel.value || 'all';
      var priority = prioritySel && prioritySel.value || 'all';
      rows.forEach(function(row){
        var c = row.getAttribute('data-company');
        var u = row.getAttribute('data-unit');
        var s = row.getAttribute('data-status');
        var p = row.getAttribute('data-priority');
        var visible = true;
        if(company !== 'all' && c !== company) visible = false;
        if(unit !== 'all' && u !== unit) visible = false;
        if(status !== 'all' && s !== status) visible = false;
        if(priority !== 'all' && p !== priority) visible = false;
        row.style.display = visible ? '' : 'none';
      });
    }

    if(companySel) companySel.addEventListener('change', applyTicketsFilters);
    if(unitSel) unitSel.addEventListener('change', applyTicketsFilters);
    if(statusSel) statusSel.addEventListener('change', applyTicketsFilters);
    if(prioritySel) prioritySel.addEventListener('change', applyTicketsFilters);
    applyTicketsFilters();
  }

  // Run after DOM ready
  if(document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', function(){
      try { initUnitsFilters(); initTicketsFilters(); } catch(e){ console.warn(e); }
    });
  } else {
    try { initUnitsFilters(); initTicketsFilters(); } catch(e){ console.warn(e); }
  }
})();
