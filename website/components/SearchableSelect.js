"use client";

import { useEffect, useRef, useState } from "react";
import { FiChevronDown, FiSearch } from "react-icons/fi";

export default function SearchableSelect({
  options,
  value,
  onChange,
  placeholder,
  disabled,
  icon: Icon,
  formatOption,
}) {
  const [isOpen, setIsOpen] = useState(false);
  const [query, setQuery] = useState("");
  const ref = useRef(null);

  const filtered = options.filter((o) =>
    o.title.toLowerCase().includes(query.toLowerCase())
  );

  const selectedOption = options.find((o) => o.id === value);

  const defaultFormat = (option) =>
    option.total_centres != null
      ? `${option.title} (${option.total_centres} ${option.total_centres === 1 ? "centre" : "centres"})`
      : option.title;
  const format = formatOption || defaultFormat;

  useEffect(() => {
    const handleClickOutside = (e) => {
      if (ref.current && !ref.current.contains(e.target)) setIsOpen(false);
    };
    document.addEventListener("mousedown", handleClickOutside);
    return () => document.removeEventListener("mousedown", handleClickOutside);
  }, []);

  return (
    <div ref={ref} className="relative">
      <button
        type="button"
        onClick={() => {
          if (!disabled) {
            setIsOpen(!isOpen);
            setQuery("");
          }
        }}
        className={`w-full flex items-center gap-2 pl-10 pr-10 py-2.5 rounded-xl text-sm text-left transition-all ${
          disabled
            ? "bg-gray-100/50 border border-gray-200 text-gray-400 cursor-not-allowed"
            : selectedOption
            ? "bg-yellow-400/15 border border-yellow-400/40 text-yellow-800 font-medium"
            : isOpen
            ? "border border-yellow-400 ring-2 ring-yellow-400/20 bg-white"
            : "bg-gray-50 border border-gray-200 hover:border-gray-300 hover:bg-gray-100 text-gray-600"
        }`}
      >
        {selectedOption ? format(selectedOption) : placeholder}
      </button>
      {Icon && (
        <Icon
          className={`absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 pointer-events-none ${
            selectedOption && !disabled ? "text-yellow-600" : "text-gray-400"
          }`}
        />
      )}
      <FiChevronDown
        className={`absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 w-3.5 h-3.5 pointer-events-none transition-transform ${
          isOpen ? "rotate-180" : ""
        }`}
      />

      {isOpen && (
        <div className="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-xl shadow-xl overflow-hidden">
          <div className="p-2 border-b border-gray-100">
            <div className="relative">
              <FiSearch className="absolute left-2.5 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-gray-400" />
              <input
                type="text"
                autoFocus
                placeholder="Type to filter..."
                value={query}
                onChange={(e) => setQuery(e.target.value)}
                className="w-full pl-8 pr-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:border-yellow-400 focus:ring-1 focus:ring-yellow-100 bg-gray-50"
              />
            </div>
          </div>
          <ul className="max-h-52 overflow-y-auto py-1">
            {filtered.length > 0 ? (
              filtered.map((option) => (
                <li key={option.id}>
                  <button
                    type="button"
                    onClick={() => {
                      onChange(option);
                      setIsOpen(false);
                      setQuery("");
                    }}
                    className={`w-full text-left px-4 py-2.5 text-sm transition-colors ${
                      option.id === value
                        ? "bg-yellow-50 text-yellow-700 font-medium"
                        : "text-gray-700 hover:bg-gray-50"
                    }`}
                  >
                    {format(option)}
                  </button>
                </li>
              ))
            ) : (
              <li className="px-4 py-3 text-sm text-gray-400 text-center">
                No results found
              </li>
            )}
          </ul>
        </div>
      )}
    </div>
  );
}
