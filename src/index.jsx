import React, { useEffect, useState } from "react";
import { render } from "react-dom";
import "./index.scss";
import {
  Bar,
  BarChart,
  CartesianGrid,
  Legend,
  Tooltip,
  XAxis,
  YAxis,
} from "recharts";
import apiFetch from "@wordpress/api-fetch";
import { addQueryArgs } from "@wordpress/url";
import { SelectControl } from "@wordpress/components";

function App() {
  const [ data, setData ] = useState( [] );
  const { __ } = wp.i18n;

  const [ days, setDays ] = useState( "7" );

  const handleChange = async function ( v ) {
    const queryParams = { days: v }; // Return posts with ID = 1,2,3.

    setDays( v );

    apiFetch( { path: addQueryArgs( rc.api_url, queryParams ) }).then( ( data ) => {
      setData( data );
    });

  };

  useEffect(() => {
    handleChange( 7 );
  }, []);

  return (
    <div>
      <div className="flex">
        <div className="col">
          <h2>{__( "Chart", "react-chart" )}</h2>
        </div>
        <div className="col end">
          <SelectControl
            label=""
            value={days}
            options={[
              { label: "7 " + __( "days", "react-chart" ), value: "7" },
              { label: "15 " + __( "days", "react-chart" ), value: "15" },
              { label: "1 " + __( "month", "react-chart" ), value: "30" },
            ]}
            onChange={( day ) => handleChange( day )}
          />
        </div>
      </div>
      <BarChart
        width={ 500 }
        height={ 300 }
        data={ data }
        margin={{
          top: 5,
          right: 30,
          left: 20,
          bottom: 5,
        }}
      >
        <CartesianGrid strokeDasharray="3 3" />
        <XAxis dataKey="name" />
        <YAxis />
        <Tooltip />
        <Legend />
        <Bar dataKey="pv" fill="#8884d8" />
        <Bar dataKey="uv" fill="#82ca9d" />
      </BarChart>
    </div>
  );
}

const rootElement = document.getElementById( "react-chart" );
if ( rootElement ) {
  render(<App />, rootElement);
}
