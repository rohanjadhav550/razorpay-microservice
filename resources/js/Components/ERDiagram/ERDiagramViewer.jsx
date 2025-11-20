import { useState, useMemo } from 'react';
import TableNode from './TableNode';
import RelationshipLine, { RelationshipMarkers } from './RelationshipLine';
import {
    parseMermaidER,
    parseJSONSchema,
    calculateTablePositions,
    calculateConnectionPoints
} from './erDiagramUtils';

/**
 * ERDiagramViewer Component
 * Main container for rendering ER diagrams
 */
export default function ERDiagramViewer({
    schema,
    mermaid,
    title,
    className = ''
}) {
    const [hoveredTable, setHoveredTable] = useState(null);
    const [zoom, setZoom] = useState(1);
    const [pan, setPan] = useState({ x: 0, y: 0 });

    // Parse the schema data
    const { tables, relationships } = useMemo(() => {
        // Prefer JSON schema if available, fall back to Mermaid parsing
        if (schema) {
            return parseJSONSchema(schema);
        }
        if (mermaid) {
            return parseMermaidER(mermaid);
        }
        return { tables: {}, relationships: [] };
    }, [schema, mermaid]);

    // Calculate positions for all tables
    const tablePositions = useMemo(() => {
        return calculateTablePositions(tables, {
            startX: 50,
            startY: 50,
            tableWidth: 220,
            tableSpacingX: 100,
            tableSpacingY: 80,
            maxColumns: 3
        });
    }, [tables]);

    // Calculate SVG dimensions
    const svgDimensions = useMemo(() => {
        const tableNames = Object.keys(tablePositions);
        if (tableNames.length === 0) {
            return { width: 800, height: 600 };
        }

        let maxX = 0;
        let maxY = 0;

        for (const tableName of tableNames) {
            const pos = tablePositions[tableName];
            const table = tables[tableName];
            const height = 40 + (table.columns?.length || 0) * 24 + 16;

            maxX = Math.max(maxX, pos.x + pos.width + 50);
            maxY = Math.max(maxY, pos.y + height + 50);
        }

        return { width: Math.max(800, maxX), height: Math.max(400, maxY) };
    }, [tablePositions, tables]);

    // Handle zoom
    const handleZoomIn = () => setZoom(z => Math.min(z + 0.1, 2));
    const handleZoomOut = () => setZoom(z => Math.max(z - 0.1, 0.5));
    const handleResetZoom = () => {
        setZoom(1);
        setPan({ x: 0, y: 0 });
    };

    // Get relationships involving the hovered table
    const highlightedRelationships = useMemo(() => {
        if (!hoveredTable) return new Set();
        return new Set(
            relationships
                .filter(r => r.from === hoveredTable || r.to === hoveredTable)
                .map(r => `${r.from}-${r.to}`)
        );
    }, [hoveredTable, relationships]);

    // Get tables connected to hovered table
    const highlightedTables = useMemo(() => {
        if (!hoveredTable) return new Set([]);
        const connected = new Set([hoveredTable]);
        relationships.forEach(r => {
            if (r.from === hoveredTable) connected.add(r.to);
            if (r.to === hoveredTable) connected.add(r.from);
        });
        return connected;
    }, [hoveredTable, relationships]);

    const tableCount = Object.keys(tables).length;

    if (tableCount === 0) {
        return (
            <div className={`bg-white dark:bg-gray-800 rounded-lg p-6 ${className}`}>
                {title && (
                    <h4 className="font-medium text-gray-900 dark:text-white mb-3">
                        {title}
                    </h4>
                )}
                <div className="flex items-center justify-center h-48 text-gray-500 dark:text-gray-400">
                    <p>No schema data available</p>
                </div>
            </div>
        );
    }

    return (
        <div className={`bg-white dark:bg-gray-800 rounded-lg shadow ${className}`}>
            {/* Header */}
            <div className="flex justify-between items-center px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <div>
                    {title && (
                        <h4 className="font-medium text-gray-900 dark:text-white">
                            {title}
                        </h4>
                    )}
                    <p className="text-xs text-gray-500 dark:text-gray-400">
                        {tableCount} table{tableCount !== 1 ? 's' : ''} • {relationships.length} relationship{relationships.length !== 1 ? 's' : ''}
                    </p>
                </div>

                {/* Zoom controls */}
                <div className="flex items-center gap-2">
                    <button
                        onClick={handleZoomOut}
                        className="p-1.5 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 bg-gray-100 dark:bg-gray-700 rounded"
                        title="Zoom out"
                    >
                        <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20 12H4" />
                        </svg>
                    </button>
                    <span className="text-xs text-gray-500 dark:text-gray-400 min-w-12 text-center">
                        {Math.round(zoom * 100)}%
                    </span>
                    <button
                        onClick={handleZoomIn}
                        className="p-1.5 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 bg-gray-100 dark:bg-gray-700 rounded"
                        title="Zoom in"
                    >
                        <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 4v16m8-8H4" />
                        </svg>
                    </button>
                    <button
                        onClick={handleResetZoom}
                        className="p-1.5 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 bg-gray-100 dark:bg-gray-700 rounded ml-1"
                        title="Reset view"
                    >
                        <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                        </svg>
                    </button>
                </div>
            </div>

            {/* Diagram */}
            <div className="overflow-auto bg-gray-50 dark:bg-gray-900" style={{ maxHeight: '500px' }}>
                <svg
                    width={svgDimensions.width * zoom}
                    height={svgDimensions.height * zoom}
                    viewBox={`${-pan.x} ${-pan.y} ${svgDimensions.width} ${svgDimensions.height}`}
                    className="select-none"
                >
                    <RelationshipMarkers />

                    {/* Grid background */}
                    <defs>
                        <pattern
                            id="grid"
                            width="20"
                            height="20"
                            patternUnits="userSpaceOnUse"
                        >
                            <path
                                d="M 20 0 L 0 0 0 20"
                                fill="none"
                                className="stroke-gray-200 dark:stroke-gray-700"
                                strokeWidth="0.5"
                            />
                        </pattern>
                    </defs>
                    <rect
                        x={-pan.x}
                        y={-pan.y}
                        width={svgDimensions.width}
                        height={svgDimensions.height}
                        fill="url(#grid)"
                    />

                    {/* Relationship lines (render behind tables) */}
                    {relationships.map((rel, index) => {
                        const fromPos = tablePositions[rel.from];
                        const toPos = tablePositions[rel.to];

                        if (!fromPos || !toPos) return null;

                        const { startX, startY, endX, endY } = calculateConnectionPoints(
                            {
                                ...fromPos,
                                height: 40 + (tables[rel.from]?.columns?.length || 0) * 24 + 16
                            },
                            {
                                ...toPos,
                                height: 40 + (tables[rel.to]?.columns?.length || 0) * 24 + 16
                            }
                        );

                        return (
                            <RelationshipLine
                                key={`${rel.from}-${rel.to}-${index}`}
                                relationship={rel}
                                startX={startX}
                                startY={startY}
                                endX={endX}
                                endY={endY}
                                isHighlighted={highlightedRelationships.has(`${rel.from}-${rel.to}`)}
                            />
                        );
                    })}

                    {/* Tables */}
                    {Object.entries(tables).map(([tableName, table]) => {
                        const position = tablePositions[tableName];
                        if (!position) return null;

                        return (
                            <TableNode
                                key={tableName}
                                table={table}
                                position={{
                                    ...position,
                                    height: 40 + (table.columns?.length || 0) * 24 + 16
                                }}
                                isHighlighted={highlightedTables.has(tableName)}
                                onHover={setHoveredTable}
                            />
                        );
                    })}
                </svg>
            </div>

            {/* Legend */}
            <div className="px-4 py-2 border-t border-gray-200 dark:border-gray-700 flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                <div className="flex items-center gap-1">
                    <span className="w-3 h-3 rounded-full bg-yellow-400"></span>
                    <span>Primary Key</span>
                </div>
                <div className="flex items-center gap-1">
                    <span className="w-3 h-3 rounded-full bg-blue-400"></span>
                    <span>Foreign Key</span>
                </div>
                <div className="flex items-center gap-1">
                    <span className="text-red-500">*</span>
                    <span>Required</span>
                </div>
            </div>
        </div>
    );
}
