export default function PoiMarker({ name, category, rating, latitude, longitude, onClick }) {
  return (
    <div
      onClick={onClick}
      className="bg-white shadow-md rounded-lg p-3 cursor-pointer hover:shadow-lg transition max-w-xs"
    >
      <h4 className="font-semibold text-gray-800 mb-1">{name}</h4>
      <p className="text-xs text-gray-500 mb-2 capitalize">{category}</p>

      <div className="flex justify-between items-center mb-3">
        <span className="text-sm text-yellow-500">⭐ {rating.toFixed(1)}</span>
        <span className="text-xs text-gray-400">
          {latitude.toFixed(2)}, {longitude.toFixed(2)}
        </span>
      </div>

      <button className="w-full text-sm bg-blue-600 text-white py-1 rounded hover:bg-blue-700 transition">
        View Details
      </button>
    </div>
  );
}
