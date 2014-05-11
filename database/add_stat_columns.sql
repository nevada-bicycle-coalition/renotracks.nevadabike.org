ALTER TABLE trip
  ADD COLUMN distance_mi DOUBLE,
  ADD COLUMN elevation_gain_ft INTEGER UNSIGNED,
  ADD COLUMN elevation_loss_ft INTEGER UNSIGNED,
  ADD COLUMN top_ft INTEGER UNSIGNED,
  ADD COLUMN bottom_ft INTEGER UNSIGNED,
  ADD COLUMN center_lat DOUBLE,
  ADD COLUMN center_lng DOUBLE,
  ADD COLUMN south_bound DOUBLE,
  ADD COLUMN west_bound DOUBLE,
  ADD COLUMN north_bound DOUBLE,
  ADD COLUMN east_bound DOUBLE