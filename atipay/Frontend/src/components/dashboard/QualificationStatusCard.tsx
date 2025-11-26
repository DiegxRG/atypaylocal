import { useState, useEffect } from 'react';
// 👇 CORRECCIÓN: Ruta absoluta con alias
import api from '@/services/api'; 

// Definimos la interfaz para que TypeScript esté feliz y te ayude
interface QualificationData {
    current_points: number;
    required_points: number;
    is_qualified: boolean;
}

export default function QualificationStatusCard() {
    const [data, setData] = useState<QualificationData | null>(null);

    useEffect(() => {
        const fetchData = async () => {
            try {
                const response = await api.get('/user/qualification-status');
                setData(response.data);
            } catch (err) {
                console.error('Error:', err);
                // Si el token expiró o no autorizado, forzar recarga para login
                const e: any = err;
                if (e && (e.status === 401 || e.status === 403 || String(e.message).includes('401') || String(e.message).includes('403'))) {
                    // pequeña demora para mostrar mensaje en UI si es necesario
                    setTimeout(() => window.location.reload(), 800);
                }
            }
        };
        fetchData();
        // Listener para cambios desde admin (otra pestaña) que actualicen puntos mínimos
        const onStorage = (ev: StorageEvent) => {
            if (ev.key === 'qualification_min_points_update' && ev.newValue) {
                try {
                    // refetch
                    fetchData();
                } catch (e) {
                    console.warn('Error refetch tras storage event', e);
                }
            }
        };
        window.addEventListener('storage', onStorage);
        return () => window.removeEventListener('storage', onStorage);
    }, []);

    if (!data) return null;

    // Evitamos división por cero o nulos
    const current = data.current_points || 0;
    const required = data.required_points || 100;
    const progress = Math.min((current / required) * 100, 100);

    return (
        <div className={`mb-6 p-5 rounded-xl shadow-sm border-l-4 bg-white flex flex-col md:flex-row justify-between items-center ${data.is_qualified ? 'border-green-500' : 'border-orange-500'}`}>
            <div className="w-full">
                <div className="flex justify-between items-center mb-2">
                    <h3 className="text-gray-500 text-xs font-bold uppercase tracking-wider">Estado Mensual</h3>
                    {data.is_qualified 
                        ? <span className="px-3 py-1 bg-green-100 text-green-700 text-xs font-bold rounded-full">CALIFICADO</span>
                        : <span className="px-3 py-1 bg-orange-100 text-orange-700 text-xs font-bold rounded-full">PENDIENTE</span>
                    }
                </div>
                
                <div className="flex items-end gap-2 mb-1">
                    <span className="text-3xl font-bold text-gray-800">{current}</span>
                    <span className="text-sm text-gray-500 mb-1">/ {required} pts</span>
                </div>

                <div className="w-full bg-gray-100 rounded-full h-2.5 mt-2 overflow-hidden">
                    <div 
                        className={`h-full transition-all duration-500 ${data.is_qualified ? 'bg-green-500' : 'bg-orange-500'}`} 
                        style={{ width: `${progress}%` }}
                    ></div>
                </div>
                
                {!data.is_qualified && (
                    <p className="text-xs text-orange-600 mt-2">
                        Te faltan <b>{required - current}</b> puntos para calificar.
                    </p>
                )}
            </div>
        </div>
    );
}