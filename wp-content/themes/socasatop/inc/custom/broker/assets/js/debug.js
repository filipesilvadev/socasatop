console.log('Debug do BrokerDashboard:', {
  react: typeof React,
  reactDOM: typeof ReactDOM,
  recharts: typeof Recharts,
  container: document.getElementById('broker-dashboard-root'),
  site: typeof site !== 'undefined' ? site : 'não definido'
});