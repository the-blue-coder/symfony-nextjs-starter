"use client";

const DashboardPage: React.FC = () => {
	return (
		<div className="space-y-6">
			<div>
				<h1 className="text-2xl font-semibold">Dashboard</h1>
				<p className="text-muted-foreground text-sm">Welcome back.</p>
			</div>

			<div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
				{["Metric A", "Metric B", "Metric C"].map((label) => (
					<div key={label} className="rounded-lg border bg-card p-5 space-y-1">
						<p className="text-sm text-muted-foreground">{label}</p>
						<p className="text-2xl font-bold">—</p>
					</div>
				))}
			</div>
		</div>
	);
};

export default DashboardPage;
