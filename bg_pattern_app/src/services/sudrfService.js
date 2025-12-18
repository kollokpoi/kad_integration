const API_BASE = 'http://127.0.0.1:8000';
const API_KEY = 'dev123';

async function request(path, opts = {}) {
  const res = await fetch(`${API_BASE}${path}`, {
    ...opts,
    headers: {
      'Content-Type': 'application/json',
      'X-API-Key': API_KEY,
      ...(opts.headers || {}),
    },
  });
  if (!res.ok) {
    const err = await res.json().catch(() => ({}));
    throw new Error(err.detail || res.statusText);
  }
  return res.json();
}

export function fetchRegions() {
  return request('/regions', { method: 'GET' });
}

export function fetchCourts(regionId) {
  return request(`/regions/${regionId}/courts`, { method: 'GET' });
}

export function searchCases({ site, surname, case_number, uid }) {
  return request('/cases/search', {
    method: 'POST',
    body: JSON.stringify({ site, surname, case_number, uid, process_cases: false }),
  });
}

export function processCases(links) {
  return request('/cases/process', {
    method: 'POST',
    body: JSON.stringify({ links }),
  });
}
