/**
 * ER Diagram Utilities
 * Parse schema data and calculate layout positions
 */

/**
 * Parse Mermaid ER diagram syntax into structured data
 * @param {string} mermaidCode - Mermaid erDiagram code
 * @returns {Object} Parsed schema with tables and relationships
 */
export function parseMermaidER(mermaidCode) {
    const tables = {};
    const relationships = [];

    if (!mermaidCode) {
        return { tables, relationships };
    }

    const lines = mermaidCode.split('\n').map(line => line.trim()).filter(line => line);

    let currentTable = null;

    for (const line of lines) {
        // Skip erDiagram declaration
        if (line === 'erDiagram' || line.startsWith('%%')) {
            continue;
        }

        // Parse table definition: TableName {
        const tableMatch = line.match(/^(\w+)\s*\{$/);
        if (tableMatch) {
            currentTable = tableMatch[1];
            tables[currentTable] = {
                name: currentTable,
                columns: []
            };
            continue;
        }

        // Parse closing brace
        if (line === '}') {
            currentTable = null;
            continue;
        }

        // Parse column definition inside table: type name PK/FK "comment"
        if (currentTable && !line.includes('||') && !line.includes('|{')) {
            const columnMatch = line.match(/^(\w+)\s+(\w+)(?:\s+(PK|FK))?(?:\s+"([^"]*)")?$/);
            if (columnMatch) {
                tables[currentTable].columns.push({
                    type: columnMatch[1],
                    name: columnMatch[2],
                    constraint: columnMatch[3] || null,
                    comment: columnMatch[4] || null
                });
            }
            continue;
        }

        // Parse relationship: Table1 ||--o{ Table2 : "relationship"
        const relMatch = line.match(/^(\w+)\s*(\|{1,2}|}{1,2})?(--|\.\.)(\|{1,2}|o{|{o|o\||{|}|o\{|\}o)?\s*(\w+)\s*:\s*"?([^"]*)"?$/);
        if (relMatch) {
            relationships.push({
                from: relMatch[1],
                to: relMatch[5],
                label: relMatch[6] || '',
                fromCardinality: parseCardinality(relMatch[2] || ''),
                toCardinality: parseCardinality(relMatch[4] || '')
            });
        }
    }

    return { tables, relationships };
}

/**
 * Parse cardinality notation to human-readable form
 */
function parseCardinality(notation) {
    if (notation.includes('||')) return 'one';
    if (notation.includes('o{') || notation.includes('{o')) return 'zero-or-many';
    if (notation.includes('|{') || notation.includes('{|')) return 'one-or-many';
    if (notation.includes('o|') || notation.includes('|o')) return 'zero-or-one';
    return 'one';
}

/**
 * Parse JSON schema (from proposed_changes) into structured data
 * @param {Object|string} schema - Schema data (can be JSON string or object)
 * @returns {Object} Parsed schema with tables and relationships
 */
export function parseJSONSchema(schema) {
    if (!schema) {
        return { tables: {}, relationships: [] };
    }

    let schemaData = schema;
    if (typeof schema === 'string') {
        try {
            schemaData = JSON.parse(schema);
        } catch (e) {
            return { tables: {}, relationships: [] };
        }
    }

    const tables = {};
    const relationships = [];

    // Handle array of tables
    if (Array.isArray(schemaData)) {
        for (const table of schemaData) {
            if (table.name && table.columns) {
                tables[table.name] = {
                    name: table.name,
                    columns: table.columns.map(col => ({
                        name: col.name || col.Field || col,
                        type: col.type || col.Type || 'unknown',
                        constraint: col.Key === 'PRI' ? 'PK' : col.Key === 'MUL' ? 'FK' : null,
                        nullable: col.Null === 'YES' || col.nullable
                    }))
                };

                // Extract foreign key relationships
                if (table.columns) {
                    for (const col of table.columns) {
                        if (col.Key === 'MUL' && col.name?.endsWith('_id')) {
                            const referencedTable = col.name.replace('_id', '') + 's';
                            relationships.push({
                                from: table.name,
                                to: referencedTable,
                                fromColumn: col.name,
                                toColumn: 'id',
                                fromCardinality: 'many',
                                toCardinality: 'one'
                            });
                        }
                    }
                }
            }
        }
    }
    // Handle object with table names as keys
    else if (typeof schemaData === 'object') {
        for (const [tableName, tableData] of Object.entries(schemaData)) {
            if (Array.isArray(tableData)) {
                tables[tableName] = {
                    name: tableName,
                    columns: tableData.map(col => ({
                        name: col.name || col.Field || col.COLUMN_NAME || '',
                        type: col.type || col.Type || col.DATA_TYPE || 'unknown',
                        constraint: col.Key === 'PRI' || col.COLUMN_KEY === 'PRI' ? 'PK' :
                                   col.Key === 'MUL' || col.COLUMN_KEY === 'MUL' ? 'FK' : null,
                        nullable: col.Null === 'YES' || col.IS_NULLABLE === 'YES' || col.nullable
                    }))
                };
            }
        }

        // Detect relationships from foreign keys
        for (const [tableName, tableData] of Object.entries(tables)) {
            for (const col of tableData.columns) {
                if (col.name?.endsWith('_id')) {
                    // Try to find the referenced table
                    const baseName = col.name.replace('_id', '');
                    const possibleTables = [
                        baseName,
                        baseName + 's',
                        baseName + 'es',
                        baseName.replace(/y$/, 'ies')
                    ];

                    for (const possibleTable of possibleTables) {
                        if (tables[possibleTable]) {
                            relationships.push({
                                from: tableName,
                                to: possibleTable,
                                fromColumn: col.name,
                                toColumn: 'id',
                                fromCardinality: 'many',
                                toCardinality: 'one'
                            });
                            break;
                        }
                    }
                }
            }
        }
    }

    return { tables, relationships };
}

/**
 * Calculate table positions for grid layout
 * @param {Object} tables - Tables object with table names as keys
 * @param {Object} options - Layout options
 * @returns {Object} Tables with calculated positions
 */
export function calculateTablePositions(tables, options = {}) {
    const {
        startX = 50,
        startY = 50,
        tableWidth = 220,
        tableSpacingX = 80,
        tableSpacingY = 60,
        maxColumns = 3
    } = options;

    const tableNames = Object.keys(tables);
    const positions = {};

    tableNames.forEach((tableName, index) => {
        const row = Math.floor(index / maxColumns);
        const col = index % maxColumns;

        // Calculate table height based on number of columns
        const columnCount = tables[tableName].columns?.length || 0;
        const tableHeight = 40 + (columnCount * 24) + 16; // Header + columns + padding

        positions[tableName] = {
            x: startX + col * (tableWidth + tableSpacingX),
            y: startY + row * (tableHeight + tableSpacingY),
            width: tableWidth,
            height: tableHeight
        };
    });

    return positions;
}

/**
 * Calculate connection points between tables for relationship lines
 * @param {Object} fromPos - Position of source table
 * @param {Object} toPos - Position of target table
 * @returns {Object} Start and end points for the line
 */
export function calculateConnectionPoints(fromPos, toPos) {
    const fromCenterX = fromPos.x + fromPos.width / 2;
    const fromCenterY = fromPos.y + fromPos.height / 2;
    const toCenterX = toPos.x + toPos.width / 2;
    const toCenterY = toPos.y + toPos.height / 2;

    let startX, startY, endX, endY;

    // Determine which edges to connect
    const dx = toCenterX - fromCenterX;
    const dy = toCenterY - fromCenterY;

    if (Math.abs(dx) > Math.abs(dy)) {
        // Connect horizontally
        if (dx > 0) {
            // Target is to the right
            startX = fromPos.x + fromPos.width;
            endX = toPos.x;
        } else {
            // Target is to the left
            startX = fromPos.x;
            endX = toPos.x + toPos.width;
        }
        startY = fromCenterY;
        endY = toCenterY;
    } else {
        // Connect vertically
        if (dy > 0) {
            // Target is below
            startY = fromPos.y + fromPos.height;
            endY = toPos.y;
        } else {
            // Target is above
            startY = fromPos.y;
            endY = toPos.y + toPos.height;
        }
        startX = fromCenterX;
        endX = toCenterX;
    }

    return { startX, startY, endX, endY };
}

/**
 * Get color for column type
 * @param {string} type - Column data type
 * @returns {string} Color class
 */
export function getTypeColor(type) {
    if (!type) return 'text-gray-500';

    const typeStr = type.toLowerCase();

    if (typeStr.includes('int') || typeStr.includes('decimal') || typeStr.includes('float') || typeStr.includes('double')) {
        return 'text-blue-600 dark:text-blue-400';
    }
    if (typeStr.includes('varchar') || typeStr.includes('text') || typeStr.includes('char') || typeStr.includes('string')) {
        return 'text-green-600 dark:text-green-400';
    }
    if (typeStr.includes('date') || typeStr.includes('time')) {
        return 'text-purple-600 dark:text-purple-400';
    }
    if (typeStr.includes('bool')) {
        return 'text-orange-600 dark:text-orange-400';
    }
    if (typeStr.includes('json') || typeStr.includes('blob')) {
        return 'text-yellow-600 dark:text-yellow-400';
    }

    return 'text-gray-600 dark:text-gray-400';
}

/**
 * Format column type for display
 * @param {string} type - Column data type
 * @returns {string} Formatted type string
 */
export function formatType(type) {
    if (!type) return 'unknown';

    // Shorten common types
    return type
        .replace('bigint unsigned', 'bigint')
        .replace('varchar(255)', 'varchar')
        .replace('timestamp', 'datetime')
        .toLowerCase();
}
