/**
 * RelationshipLine Component
 * Draws a line between two tables showing their relationship
 */
export default function RelationshipLine({
    relationship,
    startX,
    startY,
    endX,
    endY,
    isHighlighted = false
}) {
    // Calculate control points for curved line
    const midX = (startX + endX) / 2;
    const midY = (startY + endY) / 2;

    // Create a curved path
    const dx = endX - startX;
    const dy = endY - startY;

    let path;
    if (Math.abs(dx) > Math.abs(dy)) {
        // Horizontal connection - use S-curve
        path = `M ${startX} ${startY}
                C ${startX + dx / 3} ${startY},
                  ${endX - dx / 3} ${endY},
                  ${endX} ${endY}`;
    } else {
        // Vertical connection - use S-curve
        path = `M ${startX} ${startY}
                C ${startX} ${startY + dy / 3},
                  ${endX} ${endY - dy / 3},
                  ${endX} ${endY}`;
    }

    return (
        <g>
            {/* Main line */}
            <path
                d={path}
                fill="none"
                className={`transition-colors ${
                    isHighlighted
                        ? 'stroke-indigo-500 dark:stroke-indigo-400'
                        : 'stroke-gray-400 dark:stroke-gray-500'
                }`}
                strokeWidth={isHighlighted ? 2 : 1.5}
                markerEnd={`url(#${relationship.toCardinality === 'many' ? 'arrow-many' : 'arrow-one'})`}
                markerStart={`url(#${relationship.fromCardinality === 'many' ? 'dot-many' : 'dot-one'})`}
            />

            {/* Relationship label */}
            {relationship.label && (
                <g transform={`translate(${midX}, ${midY})`}>
                    <rect
                        x={-relationship.label.length * 3 - 4}
                        y="-10"
                        width={relationship.label.length * 6 + 8}
                        height="16"
                        rx="3"
                        className={`${
                            isHighlighted
                                ? 'fill-indigo-100 dark:fill-indigo-900'
                                : 'fill-white dark:fill-gray-800'
                        }`}
                    />
                    <text
                        textAnchor="middle"
                        y="2"
                        className={`${
                            isHighlighted
                                ? 'fill-indigo-700 dark:fill-indigo-300'
                                : 'fill-gray-600 dark:fill-gray-400'
                        }`}
                        style={{ fontSize: '9px' }}
                    >
                        {relationship.label}
                    </text>
                </g>
            )}
        </g>
    );
}

/**
 * SVG Defs for relationship markers
 */
export function RelationshipMarkers() {
    return (
        <defs>
            {/* Arrow for "one" relationship end */}
            <marker
                id="arrow-one"
                viewBox="0 0 10 10"
                refX="9"
                refY="5"
                markerWidth="6"
                markerHeight="6"
                orient="auto-start-reverse"
            >
                <path
                    d="M 0 2 L 6 5 L 0 8 L 2 5 Z"
                    className="fill-gray-400 dark:fill-gray-500"
                />
            </marker>

            {/* Crow's foot for "many" relationship end */}
            <marker
                id="arrow-many"
                viewBox="0 0 12 12"
                refX="10"
                refY="6"
                markerWidth="8"
                markerHeight="8"
                orient="auto-start-reverse"
            >
                <path
                    d="M 0 3 L 8 6 M 0 6 L 8 6 M 0 9 L 8 6"
                    fill="none"
                    className="stroke-gray-400 dark:stroke-gray-500"
                    strokeWidth="1.5"
                />
            </marker>

            {/* Circle for "one" relationship start */}
            <marker
                id="dot-one"
                viewBox="0 0 10 10"
                refX="1"
                refY="5"
                markerWidth="6"
                markerHeight="6"
                orient="auto-start-reverse"
            >
                <circle
                    cx="5"
                    cy="5"
                    r="2"
                    className="fill-gray-400 dark:fill-gray-500"
                />
            </marker>

            {/* Double circle for "many" relationship start */}
            <marker
                id="dot-many"
                viewBox="0 0 10 10"
                refX="1"
                refY="5"
                markerWidth="6"
                markerHeight="6"
                orient="auto-start-reverse"
            >
                <circle
                    cx="3"
                    cy="5"
                    r="2"
                    className="fill-gray-400 dark:fill-gray-500"
                />
                <circle
                    cx="7"
                    cy="5"
                    r="2"
                    className="stroke-gray-400 dark:stroke-gray-500 fill-white dark:fill-gray-800"
                    strokeWidth="1"
                />
            </marker>
        </defs>
    );
}
