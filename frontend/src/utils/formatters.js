export const formatCurrency = (value) => {
  if (value === null || value === undefined || isNaN(Number(value))) {
    return '₹0.00'; // Or "N/A" if preferred, but usually 0 for calculation pending
  }
  return `₹${Number(value).toLocaleString('en-IN', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  })}`;
};

export const formatKm = (value) => {
  return `${Number(value).toFixed(1)} km`;
};

export const formatMinutes = (minutes) => {
  const hours = Math.floor(minutes / 60);
  const mins = Math.round(minutes % 60);
  if (hours > 0) {
    return `${hours}h ${mins}m`;
  }
  return `${mins}m`;
};
