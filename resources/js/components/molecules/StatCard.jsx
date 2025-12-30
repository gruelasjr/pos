import { Card, CardBody } from "../atoms/Card";
import { Text } from "../atoms/Text";
import { Badge } from "../atoms/Badge";

/**
 * StatCard Molecule
 *
 * Displays a key metric with optional trend indicator.
 */
const StatCard = ({ label, value, hint, trend }) => {
    const trendValue = trend ?? undefined;
    const isTrendPositive = trendValue !== undefined && trendValue >= 0;

    return (
        <Card>
            <CardBody className="space-y-3">
                <Text size="sm" tone="secondary" weight="medium">
                    {label}
                </Text>
                <div className="flex items-end justify-between">
                    <Text as="p" size="3xl" weight="bold">
                        {value}
                    </Text>
                    {trendValue !== undefined && (
                        <Badge
                            variant={isTrendPositive ? "success" : "danger"}
                            size="sm"
                        >
                            {isTrendPositive ? "↑" : "↓"} {Math.abs(trendValue)}
                            %
                        </Badge>
                    )}
                </div>
                {hint && (
                    <Text size="xs" tone="tertiary">
                        {hint}
                    </Text>
                )}
            </CardBody>
        </Card>
    );
};

export { StatCard };
export default StatCard;
