import axios from 'axios'

// Zet globale defaults (CSRF + XHR)
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'

// Axios automatically adds the X-XSRF-TOKEN header if the XSRF-TOKEN cookie is present.
// No manual configuration needed for CSRF.

// (optioneel) exporteren zodat je axios elders kan importeren
export { axios }
