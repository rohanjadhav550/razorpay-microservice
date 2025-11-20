import { getTypeColor, formatType } from './erDiagramUtils';

/**
 * TableNode Component
 * Renders a single database table with its columns
 */
export default function TableNode({ table, position, isHighlighted = false, onHover }) {
    const { name, columns = [] } = table;
    const { x, y, width } = position;

    return (
        <g
            transform={`translate(${x}, ${y})`}
            onMouseEnter={() => onHover?.(name)}
            onMouseLeave={() => onHover?.(null)}
            className="cursor-pointer"
        >
            {/* Table shadow */}
            <rect
                x="2"
                y="2"
                width={width}
                height={40 + (columns.length * 24) + 8}
                rx="6"
                className="fill-gray-200 dark:fill-gray-900"
            />

            {/* Table background */}
            <rect
                x="0"
                y="0"
                width={width}
                height={40 + (columns.length * 24) + 8}
                rx="6"
                className={`fill-white dark:fill-gray-800 stroke-2 transition-colors ${
                    isHighlighted
                        ? 'stroke-indigo-500 dark:stroke-indigo-400'
                        : 'stroke-gray-300 dark:stroke-gray-600'
                }`}
            />

            {/* Table header */}
            <rect
                x="0"
                y="0"
                width={width}
                height="36"
                rx="6"
                className={`transition-colors ${
                    isHighlighted
                        ? 'fill-indigo-500 dark:fill-indigo-600'
                        : 'fill-gray-700 dark:fill-gray-700'
                }`}
            />
            {/* Square off bottom corners of header */}
            <rect
                x="0"
                y="20"
                width={width}
                height="16"
                className={`transition-colors ${
                    isHighlighted
                        ? 'fill-indigo-500 dark:fill-indigo-600'
                        : 'fill-gray-700 dark:fill-gray-700'
                }`}
            />

            {/* Table name */}
            <text
                x={width / 2}
                y="24"
                textAnchor="middle"
                className="fill-white text-sm font-semibold"
                style={{ fontSize: '12px', fontWeight: '600' }}
            >
                {name}
            </text>

            {/* Columns */}
            {columns.map((column, index) => (
                <g key={column.name} transform={`translate(0, ${40 + index * 24})`}>
                    {/* Column row background on hover */}
                    <rect
                        x="4"
                        y="0"
                        width={width - 8}
                        height="22"
                        rx="2"
                        className="fill-transparent hover:fill-gray-100 dark:hover:fill-gray-700/50 transition-colors"
                    />

                    {/* Primary key icon */}
                    {column.constraint === 'PK' && (
                        <g transform="translate(12, 11)">
                            <circle r="4" className="fill-yellow-400" />
                            <text
                                textAnchor="middle"
                                y="3"
                                className="fill-yellow-800"
                                style={{ fontSize: '6px', fontWeight: 'bold' }}
                            >
                                K
                            </text>
                        </g>
                    )}

                    {/* Foreign key icon */}
                    {column.constraint === 'FK' && (
                        <g transform="translate(12, 11)">
                            <circle r="4" className="fill-blue-400" />
                            <text
                                textAnchor="middle"
                                y="3"
                                className="fill-blue-800"
                                style={{ fontSize: '6px', fontWeight: 'bold' }}
                            >
                                F
                            </text>
                        </g>
                    )}

                    {/* Column name */}
                    <text
                        x={column.constraint ? 24 : 12}
                        y="15"
                        className="fill-gray-900 dark:fill-gray-100"
                        style={{ fontSize: '11px' }}
                    >
                        {column.name}
                        {column.nullable === false && (
                            <tspan className="fill-red-500" style={{ fontSize: '9px' }}> *</tspan>
                        )}
                    </text>

                    {/* Column type */}
                    <text
                        x={width - 12}
                        y="15"
                        textAnchor="end"
                        className={getTypeColor(column.type)}
                        style={{ fontSize: '10px' }}
                    >
                        {formatType(column.type)}
                    </text>
                </g>
            ))}
        </g>
    );
}
