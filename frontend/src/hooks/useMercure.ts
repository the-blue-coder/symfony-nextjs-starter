"use client";

import { useEffect, useRef, useState } from "react";
import { MERCURE_URL } from "@/constants/app";

const useMercure = <T = unknown>(topic: string): TUseMercureReturn<T> => {
	const [data, setData] = useState<T | null>(null);
	const [isConnected, setIsConnected] = useState(false);
	const eventSourceRef = useRef<EventSource | null>(null);

	// Subscribe to the Mercure hub for the given topic and update data on each message
	useEffect(() => {
		if (!MERCURE_URL) {
			return;
		}

		const url = new URL(MERCURE_URL);
		url.searchParams.append("topic", topic);

		const eventSource = new EventSource(url.toString(), { withCredentials: true });
		eventSourceRef.current = eventSource;

		eventSource.onopen = () => setIsConnected(true);

		eventSource.onmessage = (event) => {
			try {
				setData(JSON.parse(event.data) as T);
			} catch {
				// ignore malformed messages
			}
		};

		eventSource.onerror = () => setIsConnected(false);

		return () => {
			eventSource.close();
			setIsConnected(false);
		};
	}, [topic]);

	return { data, isConnected };
};

type TUseMercureReturn<T> = {
	data: T | null;
	isConnected: boolean;
};

export default useMercure;
