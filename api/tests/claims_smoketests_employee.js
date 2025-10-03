// Node-based smoke test for HMO Claims (employee)
// Requires node and node-fetch installed. This script assumes you have a valid session cookie saved in cookie_employee.txt

const fetch = require('node-fetch');
const fs = require('fs');
const COOKIE = fs.existsSync('cookie_employee.txt') ? fs.readFileSync('cookie_employee.txt','utf8').trim() : '';
const BASE = 'http://localhost/php/api/';

(async ()=>{
  try{
    // File a claim as employee (enrollment must belong to session employee)
    let res = await fetch(BASE+'hmo_claims.php', {
      method: 'POST', headers: {'Content-Type':'application/json', 'Cookie': COOKIE}, body: JSON.stringify({ enrollment_id: 1, claim_date: '2025-10-04', hospital_clinic: 'ACME Hospital', diagnosis: 'Flu', claim_amount: 1500.00, remarks: 'ER visit' })
    });
    let j = await res.json(); console.log('file-as-employee', j);
    const id = j.id; if (!id) return;

    // Employee update remarks
    res = await fetch(BASE+`hmo_claims.php?id=${id}`, { method: 'PUT', headers: {'Content-Type':'application/json', 'Cookie': COOKIE}, body: JSON.stringify({ remarks: 'Employee updated remarks' }) }); j = await res.json(); console.log('update-as-employee', j);

    // Attempt delete (should be forbidden)
    res = await fetch(BASE+`hmo_claims.php?id=${id}`, { method: 'DELETE', headers: {'Cookie': COOKIE} }); j = await res.json(); console.log('delete-as-employee', j);
  }catch(e){ console.error(e); }
})();
