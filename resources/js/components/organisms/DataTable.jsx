import { Card, CardBody } from "../atoms/Card";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeaderCell,
    TableRow,
} from "../atoms/Table";
import { Text } from "../atoms/Text";

/**
 * DataTable Organism
 *
 * Complete data table with theming and sorting support.
 * Composes table atoms for consistent styling.
 */
const DataTable = ({ columns, data, emptyMessage = "Sin datos" }) => (
    <Card className="overflow-hidden">
        <CardBody className="p-0">
            <div className="overflow-x-auto">
                <Table>
                    <TableHead>
                        <TableRow>
                            {columns.map((column) => (
                                <TableHeaderCell key={column.key}>
                                    {column.title}
                                </TableHeaderCell>
                            ))}
                        </TableRow>
                    </TableHead>
                    <TableBody>
                        {data.length === 0 && (
                            <TableRow>
                                <TableCell
                                    colSpan={columns.length}
                                    className="text-center"
                                >
                                    <Text size="sm" tone="tertiary">
                                        {emptyMessage}
                                    </Text>
                                </TableCell>
                            </TableRow>
                        )}
                        {data.map((row) => (
                            <TableRow key={row.id || JSON.stringify(row)}>
                                {columns.map((column) => (
                                    <TableCell key={column.key}>
                                        {typeof column.render === "function"
                                            ? column.render(
                                                  row[column.key],
                                                  row
                                              )
                                            : row[column.key]}
                                    </TableCell>
                                ))}
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>
        </CardBody>
    </Card>
);

export default DataTable;
